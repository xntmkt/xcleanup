<?php

/**
 * Copyright (c) 2026 xNetVN Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Website: https://xnetvn.com/
 * Contact: license@xnetvn.net
 */

declare(strict_types=1);

namespace XNetVN\Cleanup\Application\Cleanup;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use XNetVN\Cleanup\Application\Config\ConfigLoader;
use XNetVN\Cleanup\Application\Filesystem\DiskUsageReader;
use XNetVN\Cleanup\Application\Filesystem\FilesystemScanner;
use XNetVN\Cleanup\Application\Logging\LoggerFactory;
use XNetVN\Cleanup\Application\Notification\DiscordNotifier;
use XNetVN\Cleanup\Application\Notification\EmailNotifier;
use XNetVN\Cleanup\Application\Notification\NotifierComposite;
use XNetVN\Cleanup\Application\Notification\NotifierInterface;
use XNetVN\Cleanup\Application\Notification\SlackNotifier;
use XNetVN\Cleanup\Application\Notification\TelegramNotifier;
use XNetVN\Cleanup\Application\Util\RandomIdGenerator;

/**
 * @phpstan-import-type CleanupConfig from ConfigLoader
 */

/**
 * CLI entrypoint for cleanup operations.
 */
final class CleanupCommand extends Command
{
    protected static string $defaultName = 'cleanup';

    /**
     * Configures the CLI command description and options.
     *
     * @return void
     *
     * @example
     *  $command->configure();
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Run the cleanup job based on configuration.')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Path to config file.', 'config/config.php')
            ->addOption('quiet', null, InputOption::VALUE_NONE, 'Run without confirmation prompt.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview cleanup without deleting any data.');
    }

    /**
     * Executes the cleanup workflow for the current CLI invocation.
     *
     * @param InputInterface $input CLI input instance.
     * @param OutputInterface $output CLI output instance.
     *
     * @return int Symfony command exit code.
     *
     * @example
     *  $exitCode = $command->execute($input, $output);
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configPath = $this->getStringOption($input, 'config');
        $quiet = $this->getBoolOption($input, 'quiet');
        $dryRun = $this->getBoolOption($input, 'dry-run');
        $logger = null;

        try {
            $config = (new ConfigLoader())->load($configPath);
            /** @var CleanupConfig $config */
            $logger = $this->createLogger($config);

            $diskUsage = (new DiskUsageReader())->read((string) $config['disk_check_path']);
            $emergency = $this->isEmergency($config, $diskUsage->getFreePercent(), $diskUsage->getFreeBytes());

            $stateStore = new CleanupStateStore((string) $config['logging']['state_file']);
            $planner = new CleanupPlanner(new FilesystemScanner(), $stateStore);
            $plan = $planner->plan($config, $diskUsage, $emergency);

            if ($plan->getItems() === []) {
                $output->writeln('No items to delete.');
                return Command::SUCCESS;
            }

            if (!$quiet) {
                if (!$this->confirmExecution($input, $output, $plan, (string) $config['logging']['directory'])) {
                    $output->writeln('Cleanup canceled by user.');
                    return Command::SUCCESS;
                }
            }

            $report = new CleanupReport();
            if ($dryRun) {
                $summary = $report->renderDryRunSummary(
                    $plan->getItems(),
                    $plan->getDiskUsage(),
                    $plan->getTotalSizeBytes(),
                    $plan->isEmergency()
                );
                $detail = $report->renderDryRunDetail($plan->getItems());
                $reportPaths = $this->writeReports((string) $config['logging']['directory'], $summary, $detail);

                $this->sendNotifications($config, $logger, $summary, $reportPaths['detail'], true);

                $output->writeln('Dry-run completed.');
                $output->writeln(sprintf('Summary report: %s', $reportPaths['summary']));
                $output->writeln(sprintf('Detail report: %s', $reportPaths['detail']));

                return Command::SUCCESS;
            }

            $executor = new CleanupExecutor($stateStore);
            $result = $executor->execute($plan, $logger);

            $summary = $report->renderExecutionSummary(
                $result->getDeletedItems(),
                $plan->getDiskUsage(),
                $plan->getTotalSizeBytes(),
                $plan->isEmergency()
            );
            $detail = $report->renderExecutionDetail($result->getDeletedItems());

            $reportPaths = $this->writeReports((string) $config['logging']['directory'], $summary, $detail);

            $this->sendNotifications($config, $logger, $summary, $reportPaths['detail'], false);

            $output->writeln('Cleanup completed.');
            $output->writeln(sprintf('Summary report: %s', $reportPaths['summary']));
            $output->writeln(sprintf('Detail report: %s', $reportPaths['detail']));

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            if ($logger instanceof LoggerInterface) {
                $logger->error('Cleanup failed', ['error' => $exception->getMessage()]);
            }

            $output->writeln(sprintf('Cleanup failed: %s', $exception->getMessage()));
            return Command::FAILURE;
        }
    }

    /**
     * @param CleanupConfig $config
     *
     * @return LoggerInterface
     *
     * @throws \XNetVN\Cleanup\Domain\Exception\FilesystemException When the log directory cannot be created.
     *
     * @example
     *  $logger = $this->createLogger($config);
     */
    private function createLogger(array $config): LoggerInterface
    {
        $factory = new LoggerFactory();

        return $factory->create(
            (string) $config['logging']['directory'],
            (string) $config['logging']['level'],
            (bool) $config['logging']['json_logs']
        );
    }

    /**
     * @param CleanupConfig $config
     * @param float $freePercent Current free space percentage.
     * @param int $freeBytes Current free space bytes.
     *
     * @return bool True when emergency cleanup should run.
     *
     * @example
     *  $emergency = $this->isEmergency($config, 4.2, 1024);
     */
    private function isEmergency(array $config, float $freePercent, int $freeBytes): bool
    {
        if (!(bool) $config['emergency']['enabled']) {
            return false;
        }

        $percentThreshold = (float) $config['emergency']['free_percent_threshold'];
        $bytesThreshold = (int) $config['emergency']['free_bytes_threshold'];
        $bytesCritical = (int) $config['emergency']['free_bytes_critical_threshold'];

        return $freePercent < $percentThreshold || $freeBytes < $bytesThreshold || $freeBytes < $bytesCritical;
    }

    /**
     * Prompts the user to confirm the cleanup execution.
     *
     * @param InputInterface $input CLI input instance.
     * @param OutputInterface $output CLI output instance.
     * @param CleanupPlan $plan Planned cleanup items.
     * @param string $logDir Log directory for confirmation files.
     *
     * @return bool True when the user confirms; otherwise false.
     *
     * @throws \RuntimeException When confirmation cannot be prepared.
     *
     * @example
     *  $confirmed = $this->confirmExecution($input, $output, $plan, '/var/log/cleanup');
     */
    private function confirmExecution(
        InputInterface $input,
        OutputInterface $output,
        CleanupPlan $plan,
        string $logDir
    ): bool {
        $generator = new RandomIdGenerator();
        $jobId = $generator->generate(6);
        $confirmKey = $generator->generate(6);
        $report = new CleanupReport();

        $content = $report->renderPlanSummary(
            $confirmKey,
            $plan->getItems(),
            $plan->getDiskUsage(),
            $plan->getTotalSizeBytes()
        );

        $confirmPath = rtrim($logDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . sprintf('job-confirm-%s.log', $jobId);

        $this->writeFile($confirmPath, $content);

        $output->writeln(sprintf('Confirmation required. Review: %s', $confirmPath));
        $helper = $this->getHelper('question');
        if (!$helper instanceof QuestionHelper) {
            throw new \RuntimeException('Question helper is not available.');
        }

        while (true) {
            $question = new Question('Enter confirm key or type "exit" to cancel: ');
            $answer = (string) $helper->ask($input, $output, $question);

            if ($answer === 'exit') {
                return false;
            }

            if ($answer === $confirmKey) {
                return true;
            }

            $output->writeln('Invalid confirm key. Try again.');
        }
    }

    /**
     * Writes summary and detail reports into the log directory.
     *
     * @param string $logDir Log directory path.
     * @param string $summary Summary report contents.
     * @param string $detail Detail report contents.
     *
     * @return array{summary: string, detail: string} Report file paths.
     *
     * @throws \RuntimeException When writing reports fails.
     *
     * @example
     *  $paths = $this->writeReports('/var/log/cleanup', $summary, $detail);
     */
    private function writeReports(string $logDir, string $summary, string $detail): array
    {
        $timestamp = date('Ymd_His');
        $summaryPath = rtrim($logDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . sprintf('cleanup-summary-%s.log', $timestamp);
        $detailPath = rtrim($logDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . sprintf('cleanup-detail-%s.log', $timestamp);

        $this->writeFile($summaryPath, $summary);
        $this->writeFile($detailPath, $detail);

        return ['summary' => $summaryPath, 'detail' => $detailPath];
    }

    /**
     * Writes content to a file, creating the directory if needed.
     *
     * @param string $path File path to write.
     * @param string $contents File contents.
     *
     * @return void
     *
     * @throws \RuntimeException When the directory or file cannot be created.
     *
     * @example
     *  $this->writeFile('/var/log/cleanup/report.log', $contents);
     */
    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            if (!@mkdir($directory, 0750, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create directory: %s', $directory));
            }
        }

        if (file_put_contents($path, $contents) === false) {
            throw new \RuntimeException(sprintf('Unable to write file: %s', $path));
        }
    }

    /**
     * @param CleanupConfig $config
     * @param LoggerInterface $logger Logger used for notification errors.
     * @param string $summary Summary report contents.
     * @param string $detailReportPath Path to detail report.
     * @param bool $dryRun Whether the run is a dry-run.
     *
     * @return void
     *
     * @example
     *  $this->sendNotifications($config, $logger, $summary, $detailPath, true);
     */
    private function sendNotifications(
        array $config,
        LoggerInterface $logger,
        string $summary,
        string $detailReportPath,
        bool $dryRun
    ): void {
        if (!(bool) $config['notifications']['enabled']) {
            return;
        }

        $notifiers = $this->buildNotifiers($config, $logger);
        if ($notifiers === []) {
            return;
        }

        $subject = sprintf(
            'Cleanup %sreport %s',
            $dryRun ? 'dry-run ' : '',
            date('Y-m-d H:i:s')
        );
        $message = $summary . "\nDetail report: " . $detailReportPath;

        (new NotifierComposite($notifiers, $logger))->sendAll($subject, $message);
    }

    /**
     * @param CleanupConfig $config
     * @param LoggerInterface $logger Logger used for enabled-but-incomplete channels.
     *
     * @return NotifierInterface[]
     *
     * @example
     *  $notifiers = $this->buildNotifiers($config, $logger);
     */
    private function buildNotifiers(array $config, LoggerInterface $logger): array
    {
        $notifiers = [];

        $email = $config['notifications']['email'];
        if ((bool) $email['enabled']) {
            if ($this->hasEmailConfig($email)) {
                $notifiers[] = new EmailNotifier(
                    (string) $email['smtp_host'],
                    (int) $email['smtp_port'],
                    (string) $email['smtp_username'],
                    (string) $email['smtp_password'],
                    (string) $email['smtp_encryption'],
                    (string) $email['from'],
                    (string) $email['to']
                );
            } else {
                $logger->warning('Email notifier enabled but configuration is incomplete.');
            }
        }

        $telegram = $config['notifications']['telegram'];
        if ((bool) $telegram['enabled']) {
            if ($telegram['bot_token'] !== '' && $telegram['chat_id'] !== '') {
                $notifiers[] = new TelegramNotifier((string) $telegram['bot_token'], (string) $telegram['chat_id']);
            } else {
                $logger->warning('Telegram notifier enabled but configuration is incomplete.');
            }
        }

        $slack = $config['notifications']['slack'];
        if ((bool) $slack['enabled']) {
            if ($slack['webhook_url'] !== '') {
                $notifiers[] = new SlackNotifier((string) $slack['webhook_url']);
            } else {
                $logger->warning('Slack notifier enabled but configuration is incomplete.');
            }
        }

        $discord = $config['notifications']['discord'];
        if ((bool) $discord['enabled']) {
            if ($discord['webhook_url'] !== '') {
                $notifiers[] = new DiscordNotifier((string) $discord['webhook_url']);
            } else {
                $logger->warning('Discord notifier enabled but configuration is incomplete.');
            }
        }

        return $notifiers;
    }

    /**
     * @param CleanupConfig['notifications']['email'] $email
     *
     * @return bool True when required SMTP fields are populated.
     *
     * @example
    *  if ($this->hasEmailConfig($emailConfig)) { // ... }
     */
    private function hasEmailConfig(array $email): bool
    {
        return $email['smtp_host'] !== ''
            && $email['smtp_username'] !== ''
            && $email['smtp_password'] !== ''
            && $email['from'] !== ''
            && $email['to'] !== '';
    }

    /**
     * Reads a required string option from the CLI input.
     *
     * @param InputInterface $input CLI input instance.
     * @param string $name Option name.
     *
     * @return string Non-empty option value.
     *
     * @throws \RuntimeException When the option is missing or empty.
     *
     * @example
     *  $configPath = $this->getStringOption($input, 'config');
     */
    private function getStringOption(InputInterface $input, string $name): string
    {
        $value = $input->getOption($name);
        if (!is_string($value) || $value === '') {
            throw new \RuntimeException(sprintf('Option "%s" must be a non-empty string.', $name));
        }

        return $value;
    }

    /**
     * Reads a boolean flag option from the CLI input.
     *
     * @param InputInterface $input CLI input instance.
     * @param string $name Option name.
     *
     * @return bool Boolean flag value.
     *
     * @throws \RuntimeException When the option value is not boolean.
     *
     * @example
     *  $dryRun = $this->getBoolOption($input, 'dry-run');
     */
    private function getBoolOption(InputInterface $input, string $name): bool
    {
        $value = $input->getOption($name);
        if (!is_bool($value)) {
            throw new \RuntimeException(sprintf('Option "%s" must be a boolean.', $name));
        }

        return $value;
    }
}
