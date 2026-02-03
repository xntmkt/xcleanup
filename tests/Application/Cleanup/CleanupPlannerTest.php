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

namespace XNetVN\Cleanup\Tests\Application\Cleanup;

use PHPUnit\Framework\TestCase;
use XNetVN\Cleanup\Application\Cleanup\CleanupPlanner;
use XNetVN\Cleanup\Application\Cleanup\CleanupStateStore;
use XNetVN\Cleanup\Application\Filesystem\DiskUsage;
use XNetVN\Cleanup\Application\Filesystem\FilesystemScanner;

/**
 * Ensures cleanup planning respects age and empty directory rules.
 */
final class CleanupPlannerTest extends TestCase
{
    public function testPlanFiltersByAgeAndEmptyDirectories(): void
    {
        $tempRoot = $this->createTempDirectory('xcleanup-plan-');
        $oldFile = $tempRoot . '/old.log';
        $newFile = $tempRoot . '/new.log';
        $emptyDir = $tempRoot . '/empty-dir';

        file_put_contents($oldFile, 'old');
        file_put_contents($newFile, 'new');
        mkdir($emptyDir, 0750, true);

        $oldTimestamp = time() - 7200;
        touch($oldFile, $oldTimestamp);
        touch($newFile, time());
        touch($emptyDir, $oldTimestamp);

        $stateFile = $tempRoot . '/state.json';
        $planner = new CleanupPlanner(new FilesystemScanner(), new CleanupStateStore($stateFile));

        $config = [
            'disk_check_path' => $tempRoot,
            'paths' => [
                'allowed_paths' => [$tempRoot],
                'excluded_paths' => [],
                'follow_symlinks' => false,
            ],
            'cleanup' => [
                'min_age_seconds' => 3600,
                'skip_if_deleted_within_seconds' => 0,
                'delete_files' => true,
                'delete_empty_directories' => true,
                'max_items' => 0,
            ],
            'emergency' => [
                'enabled' => false,
                'free_percent_threshold' => 10,
                'free_bytes_threshold' => 100,
                'free_bytes_critical_threshold' => 50,
                'paths' => [],
            ],
            'logging' => [
                'directory' => $tempRoot . '/logs',
                'level' => 'info',
                'json_logs' => false,
                'state_file' => $stateFile,
            ],
            'notifications' => [
                'enabled' => false,
                'email' => [
                    'enabled' => false,
                    'smtp_host' => 'smtp.example.com',
                    'smtp_port' => 587,
                    'smtp_username' => 'user',
                    'smtp_password' => 'pass',
                    'smtp_encryption' => 'tls',
                    'from' => 'noreply@example.com',
                    'to' => 'alerts@example.com',
                ],
                'telegram' => [
                    'enabled' => false,
                    'bot_token' => 'token',
                    'chat_id' => 'chat',
                ],
                'slack' => [
                    'enabled' => false,
                    'webhook_url' => 'https://hooks.slack.com/services/test',
                ],
                'discord' => [
                    'enabled' => false,
                    'webhook_url' => 'https://discord.com/api/webhooks/test',
                ],
            ],
        ];

        $plan = $planner->plan($config, new DiskUsage(1000, 500), false);
        $items = $plan->getItems();

        $paths = array_map(static fn ($item): string => $item->getPath(), $items);
        $this->assertContains($oldFile, $paths);
        $this->assertContains($emptyDir, $paths);
        $this->assertNotContains($newFile, $paths);

        $this->removeDirectory($tempRoot);
    }

    private function createTempDirectory(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        if ($path === false) {
            throw new \RuntimeException('Unable to create temporary directory.');
        }

        unlink($path);
        if (!mkdir($path, 0750, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to create temporary directory.');
        }

        return $path;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
