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

namespace XNetVN\Cleanup\Application\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use XNetVN\Cleanup\Domain\Exception\FilesystemException;

/**
 * Builds a PSR-3 compliant logger.
 */
final class LoggerFactory
{
    /**
     * Creates a PSR-3 logger writing to the configured directory.
     *
     * @param string $directory Log directory path.
     * @param string $level Log level name.
     * @param bool $jsonLogs Whether to emit JSON formatted logs.
     *
     * @return LoggerInterface
     *
     * @throws FilesystemException When log directory cannot be created.
     *
     * @example
     *  $logger = $factory->create('/var/log/cleanup', 'info', true);
     */
    public function create(string $directory, string $level, bool $jsonLogs): LoggerInterface
    {
        if (!is_dir($directory)) {
            if (!@mkdir($directory, 0750, true) && !is_dir($directory)) {
                throw new FilesystemException(sprintf('Unable to create log directory: %s', $directory));
            }
        }

        $logger = new Logger('cleanup');

        try {
            /** @phpstan-ignore-next-line Monolog validates supported level strings at runtime. */
            $levelValue = Logger::toMonologLevel($level);
        } catch (\InvalidArgumentException $exception) {
            throw new FilesystemException(sprintf('Invalid log level: %s', $level), 0, $exception);
        }

        $handler = new StreamHandler(
            rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cleanup.log',
            $levelValue
        );

        if ($jsonLogs) {
            $handler->setFormatter(new JsonFormatter());
        }

        $logger->pushHandler($handler);

        return $logger;
    }
}
