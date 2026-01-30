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

use Symfony\Component\HttpClient\HttpClient;

/**
 * Sends summary reports via Slack incoming webhook.
 */
final class SlackNotifier implements NotifierInterface
{
    /**
     * Configures the Slack webhook notifier.
     *
     * @param string $webhookUrl Slack incoming webhook URL.
     *
     * @example
     *  $notifier = new SlackNotifier('https://hooks.slack.com/...');
     */
    public function __construct(private string $webhookUrl)
    {
    }

    /**
     * Sends a notification to Slack via webhook.
     *
     * @param string $subject Message subject.
     * @param string $message Message body.
     *
     * @return void
     *
     * @example
     *  $notifier->send('Cleanup Report', 'Summary text');
     */
    public function send(string $subject, string $message): void
    {
        $client = HttpClient::create();
        $client->request('POST', $this->webhookUrl, [
            'json' => ['text' => $subject . "\n\n" . $message],
        ])->getContent();
    }
}
