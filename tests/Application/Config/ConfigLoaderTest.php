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

namespace XNetVN\Cleanup\Tests\Application\Config;

use PHPUnit\Framework\TestCase;
use XNetVN\Cleanup\Application\Config\ConfigLoader;
use XNetVN\Cleanup\Domain\Exception\ConfigurationException;

/**
 * Covers configuration loading and validation scenarios.
 */
final class ConfigLoaderTest extends TestCase
{
    public function testLoadReturnsConfigArray(): void
    {
        $config = [
            'disk_check_path' => '/',
            'paths' => [
                'allowed_paths' => ['/tmp'],
                'excluded_paths' => [],
                'follow_symlinks' => false,
            ],
            'cleanup' => [
                'min_age_seconds' => 0,
                'skip_if_deleted_within_seconds' => 0,
                'delete_files' => true,
                'delete_empty_directories' => true,
                'max_items' => 0,
            ],
            'emergency' => [
                'enabled' => false,
                'free_percent_threshold' => 5,
                'free_bytes_threshold' => 1024,
                'free_bytes_critical_threshold' => 512,
                'paths' => ['/tmp'],
            ],
            'logging' => [
                'directory' => '/var/log/xcleanup',
                'level' => 'info',
                'json_logs' => true,
                'state_file' => '/var/log/xcleanup/state.json',
            ],
            'notifications' => [
                'enabled' => false,
                'email' => [
                    'enabled' => false,
                    'smtp_host' => '',
                    'smtp_port' => 587,
                    'smtp_username' => '',
                    'smtp_password' => '',
                    'smtp_encryption' => 'tls',
                    'from' => '',
                    'to' => '',
                ],
                'telegram' => [
                    'enabled' => false,
                    'bot_token' => '',
                    'chat_id' => '',
                ],
                'slack' => [
                    'enabled' => false,
                    'webhook_url' => '',
                ],
                'discord' => [
                    'enabled' => false,
                    'webhook_url' => '',
                ],
            ],
        ];

        $path = $this->createTempConfig($config);

        try {
            $loader = new ConfigLoader();
            $loaded = $loader->load($path);

            $this->assertSame($config, $loaded);
        } finally {
            @unlink($path);
        }
    }

    public function testLoadThrowsWhenMissingRequiredKeys(): void
    {
        $path = $this->createTempConfig(['paths' => []]);

        try {
            $loader = new ConfigLoader();

            $this->expectException(ConfigurationException::class);
            $loader->load($path);
        } finally {
            @unlink($path);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createTempConfig(array $config): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xcleanup-config-');
        if ($path === false) {
            throw new \RuntimeException('Unable to create temporary config file.');
        }

        $contents = "<?php\nreturn " . var_export($config, true) . ";\n";
        if (file_put_contents($path, $contents) === false) {
            throw new \RuntimeException('Unable to write temporary config file.');
        }

        return $path;
    }
}
