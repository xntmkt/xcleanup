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

namespace XNetVN\Cleanup\Application\Config;

use XNetVN\Cleanup\Domain\Exception\ConfigurationException;

/**
 * Loads and validates the PHP array configuration.
 *
 * @phpstan-type PathsConfig array{
 *     allowed_paths: string[],
 *     excluded_paths: string[],
 *     follow_symlinks: bool
 * }
 * @phpstan-type CleanupConfig array{
 *     disk_check_path: string,
 *     paths: PathsConfig,
 *     cleanup: array{
 *         min_age_seconds: int,
 *         skip_if_deleted_within_seconds: int,
 *         delete_files: bool,
 *         delete_empty_directories: bool,
 *         max_items: int
 *     },
 *     emergency: array{
 *         enabled: bool,
 *         free_percent_threshold: float|int,
 *         free_bytes_threshold: int,
 *         free_bytes_critical_threshold: int,
 *         paths: string[]
 *     },
 *     logging: array{
 *         directory: string,
 *         level: string,
 *         json_logs: bool,
 *         state_file: string
 *     },
 *     notifications: array{
 *         enabled: bool,
 *         email: array{
 *             enabled: bool,
 *             smtp_host: string,
 *             smtp_port: int,
 *             smtp_username: string,
 *             smtp_password: string,
 *             smtp_encryption: string,
 *             from: string,
 *             to: string
 *         },
 *         telegram: array{
 *             enabled: bool,
 *             bot_token: string,
 *             chat_id: string
 *         },
 *         slack: array{
 *             enabled: bool,
 *             webhook_url: string
 *         },
 *         discord: array{
 *             enabled: bool,
 *             webhook_url: string
 *         }
 *     }
 * }
 */
final class ConfigLoader
{
    /**
     * Loads the configuration array from a PHP file.
     *
     * @param string $configPath Absolute path to the configuration file.
     *
     * @return CleanupConfig
     * @throws ConfigurationException When configuration is invalid.
     *
     * @example
     *  $config = $loader->load('/etc/xcleanup/config.php');
     */
    public function load(string $configPath): array
    {
        if (!is_file($configPath)) {
            throw new ConfigurationException(sprintf('Config file not found: %s', $configPath));
        }

        $config = require $configPath;

        if (!is_array($config)) {
            throw new ConfigurationException('Config file must return an array.');
        }

        $this->assertRequiredKeys($config);

        /** @var CleanupConfig $config */
        return $config;
    }

    /**
     * Validates required keys and type constraints.
     *
     * @param CleanupConfig $config
     *
     * @return void
     *
     * @throws ConfigurationException When required keys are missing or invalid.
     *
     * @example
     *  $this->assertRequiredKeys($config);
     */
    private function assertRequiredKeys(array $config): void
    {
        $required = ['disk_check_path', 'paths', 'cleanup', 'emergency', 'logging', 'notifications'];

        foreach ($required as $key) {
            if (!array_key_exists($key, $config)) {
                throw new ConfigurationException(sprintf('Missing required config key: %s', $key));
            }
        }

        $this->assertNonEmptyString($config, 'disk_check_path');
        $this->assertAbsolutePath($config['disk_check_path'], 'disk_check_path');

        foreach (['paths', 'cleanup', 'emergency', 'logging', 'notifications'] as $key) {
            if (!is_array($config[$key])) {
                throw new ConfigurationException(sprintf('Config %s must be an array.', $key));
            }
        }

        $this->assertPathsConfig($config['paths']);
        $this->assertCleanupConfig($config['cleanup']);
        $this->assertEmergencyConfig($config['emergency']);
        $this->assertLoggingConfig($config['logging']);
        $this->assertNotificationsConfig($config['notifications']);
    }

    /**
     * @param PathsConfig $paths
     *
     * @return void
     */
    private function assertPathsConfig(array $paths): void
    {
        $this->assertStringArray($paths, 'allowed_paths', true);
        $this->assertStringArray($paths, 'excluded_paths', false);
        $this->assertBool($paths, 'follow_symlinks');

        $this->assertValidPatterns($paths['allowed_paths'], 'paths.allowed_paths');
        $this->assertValidPatterns($paths['excluded_paths'], 'paths.excluded_paths');
    }

    /**
     * @param CleanupConfig['cleanup'] $cleanup
     *
     * @return void
     */
    private function assertCleanupConfig(array $cleanup): void
    {
        $this->assertNonNegativeInt($cleanup, 'min_age_seconds');
        $this->assertNonNegativeInt($cleanup, 'skip_if_deleted_within_seconds');
        $this->assertBool($cleanup, 'delete_files');
        $this->assertBool($cleanup, 'delete_empty_directories');
        $this->assertNonNegativeInt($cleanup, 'max_items');
    }

    /**
     * @param CleanupConfig['emergency'] $emergency
     *
     * @return void
     */
    private function assertEmergencyConfig(array $emergency): void
    {
        $this->assertBool($emergency, 'enabled');
        $this->assertNonNegativeNumber($emergency, 'free_percent_threshold');
        $this->assertNonNegativeInt($emergency, 'free_bytes_threshold');
        $this->assertNonNegativeInt($emergency, 'free_bytes_critical_threshold');
        $this->assertStringArray($emergency, 'paths', false);
        $this->assertValidPatterns($emergency['paths'], 'emergency.paths');
    }

    /**
     * @param CleanupConfig['logging'] $logging
     *
     * @return void
     */
    private function assertLoggingConfig(array $logging): void
    {
        $this->assertNonEmptyString($logging, 'directory');
        $this->assertAbsolutePath($logging['directory'], 'logging.directory');
        $this->assertNonEmptyString($logging, 'level');
        $this->assertBool($logging, 'json_logs');
        $this->assertNonEmptyString($logging, 'state_file');
        $this->assertAbsolutePath($logging['state_file'], 'logging.state_file');
    }

    /**
     * @param CleanupConfig['notifications'] $notifications
     *
     * @return void
     */
    private function assertNotificationsConfig(array $notifications): void
    {
        $this->assertBool($notifications, 'enabled');
        $this->assertArray($notifications, 'email');
        $this->assertArray($notifications, 'telegram');
        $this->assertArray($notifications, 'slack');
        $this->assertArray($notifications, 'discord');

        $this->assertEmailConfig($notifications['email']);
        $this->assertTelegramConfig($notifications['telegram']);
        $this->assertSlackConfig($notifications['slack']);
        $this->assertDiscordConfig($notifications['discord']);
    }

    /**
     * @param CleanupConfig['notifications']['email'] $email
     *
     * @return void
     */
    private function assertEmailConfig(array $email): void
    {
        $this->assertBool($email, 'enabled');
        $this->assertString($email, 'smtp_host');
        $this->assertPositiveInt($email, 'smtp_port');
        $this->assertString($email, 'smtp_username');
        $this->assertString($email, 'smtp_password');
        $this->assertString($email, 'smtp_encryption');
        $this->assertString($email, 'from');
        $this->assertString($email, 'to');

        if ((bool) $email['enabled']) {
            foreach (['smtp_host', 'smtp_username', 'smtp_password', 'smtp_encryption', 'from', 'to'] as $key) {
                if ($email[$key] === '') {
                    throw new ConfigurationException(sprintf('Email notification %s must be a non-empty string.', $key));
                }
            }
        }
    }

    /**
     * @param CleanupConfig['notifications']['telegram'] $telegram
     *
     * @return void
     */
    private function assertTelegramConfig(array $telegram): void
    {
        $this->assertBool($telegram, 'enabled');
        $this->assertString($telegram, 'bot_token');
        $this->assertString($telegram, 'chat_id');

        if ((bool) $telegram['enabled'] && ($telegram['bot_token'] === '' || $telegram['chat_id'] === '')) {
            throw new ConfigurationException('Telegram notification requires bot_token and chat_id.');
        }
    }

    /**
     * @param CleanupConfig['notifications']['slack'] $slack
     *
     * @return void
     */
    private function assertSlackConfig(array $slack): void
    {
        $this->assertBool($slack, 'enabled');
        $this->assertString($slack, 'webhook_url');

        if ((bool) $slack['enabled'] && $slack['webhook_url'] === '') {
            throw new ConfigurationException('Slack notification requires webhook_url.');
        }
    }

    /**
     * @param CleanupConfig['notifications']['discord'] $discord
     *
     * @return void
     */
    private function assertDiscordConfig(array $discord): void
    {
        $this->assertBool($discord, 'enabled');
        $this->assertString($discord, 'webhook_url');

        if ((bool) $discord['enabled'] && $discord['webhook_url'] === '') {
            throw new ConfigurationException('Discord notification requires webhook_url.');
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function assertArray(array $data, string $key): void
    {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            throw new ConfigurationException(sprintf('Config %s must be an array.', $key));
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function assertString(array $data, string $key): void
    {
        if (!array_key_exists($key, $data) || !is_string($data[$key])) {
            throw new ConfigurationException(sprintf('Config %s must be a string.', $key));
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function assertNonEmptyString(array $data, string $key): void
    {
        $this->assertString($data, $key);

        if ($data[$key] === '') {
            throw new ConfigurationException(sprintf('Config %s must be a non-empty string.', $key));
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function assertBool(array $data, string $key): void
    {
        if (!array_key_exists($key, $data) || !is_bool($data[$key])) {
            throw new ConfigurationException(sprintf('Config %s must be a boolean.', $key));
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function assertNonNegativeInt(array $data, string $key): void
    {
        if (!array_key_exists($key, $data) || !is_int($data[$key]) || $data[$key] < 0) {
            throw new ConfigurationException(sprintf('Config %s must be a non-negative integer.', $key));
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function assertPositiveInt(array $data, string $key): void
    {
        if (!array_key_exists($key, $data) || !is_int($data[$key]) || $data[$key] <= 0) {
            throw new ConfigurationException(sprintf('Config %s must be a positive integer.', $key));
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function assertNonNegativeNumber(array $data, string $key): void
    {
        if (!array_key_exists($key, $data) || (!is_int($data[$key]) && !is_float($data[$key])) || $data[$key] < 0) {
            throw new ConfigurationException(sprintf('Config %s must be a non-negative number.', $key));
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function assertStringArray(array $data, string $key, bool $requireNonEmpty): void
    {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            throw new ConfigurationException(sprintf('Config %s must be an array.', $key));
        }

        if ($requireNonEmpty && $data[$key] === []) {
            throw new ConfigurationException(sprintf('Config %s must contain at least one entry.', $key));
        }

        foreach ($data[$key] as $index => $value) {
            if (!is_string($value)) {
                throw new ConfigurationException(sprintf('Config %s[%d] must be a string.', $key, $index));
            }
        }
    }

    /**
     * @param string[] $patterns
     * @param string $label
     *
     * @return void
     */
    private function assertValidPatterns(array $patterns, string $label): void
    {
        foreach ($patterns as $index => $pattern) {
            if ($pattern === '') {
                throw new ConfigurationException(sprintf('Config %s[%d] must be a non-empty string.', $label, $index));
            }

            if ($this->isRegex($pattern) && @preg_match($pattern, '') === false) {
                throw new ConfigurationException(sprintf('Config %s[%d] contains invalid regex.', $label, $index));
            }
        }
    }

    /**
     * @param string $path
     * @param string $label
     *
     * @return void
     */
    private function assertAbsolutePath(string $path, string $label): void
    {
        if (!str_starts_with($path, DIRECTORY_SEPARATOR)) {
            throw new ConfigurationException(sprintf('Config %s must be an absolute path.', $label));
        }
    }

    private function isRegex(string $pattern): bool
    {
        if (strlen($pattern) < 3) {
            return false;
        }

        $delimiter = $pattern[0];
        $last = $pattern[strlen($pattern) - 1];

        return $delimiter === $last && in_array($delimiter, ['#', '/', '~'], true);
    }
}
