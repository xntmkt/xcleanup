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

namespace XNetVN\Cleanup\Application\Notification;

use Psr\Log\LoggerInterface;

/**
 * Dispatches notifications to all enabled channels.
 */
final class NotifierComposite
{
    /**
     * @param NotifierInterface[] $notifiers
     * @param LoggerInterface $logger Logger used for notification failures.
     *
     * @example
     *  $composite = new NotifierComposite($notifiers, $logger);
     */
    public function __construct(
        private array $notifiers,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Sends the notification to all configured channels.
     *
     * @param string $subject Message subject.
     * @param string $message Message body.
     *
     * @return void
     *
     * @example
     *  $composite->sendAll('Cleanup Report', 'Summary text');
     */
    public function sendAll(string $subject, string $message): void
    {
        foreach ($this->notifiers as $notifier) {
            try {
                $notifier->send($subject, $message);
            } catch (\Throwable $exception) {
                $this->logger->warning('Notification failed', [
                    'error' => $exception->getMessage(),
                    'channel' => get_class($notifier),
                ]);
            }
        }
    }
}
