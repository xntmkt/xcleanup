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

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

/**
 * Sends summary reports via SMTP.
 */
final class EmailNotifier implements NotifierInterface
{
    /**
     * Configures the SMTP email notifier.
     *
     * @param string $smtpHost SMTP host.
     * @param int $smtpPort SMTP port.
     * @param string $smtpUsername SMTP username.
     * @param string $smtpPassword SMTP password.
     * @param string $smtpEncryption Encryption method (e.g. tls).
     * @param string $from Sender email.
     * @param string $to Recipient email.
     *
     * @example
     *  $notifier = new EmailNotifier('smtp.example.com', 587, 'user', 'pass', 'tls', 'from@x.com', 'to@x.com');
     */
    public function __construct(
        private string $smtpHost,
        private int $smtpPort,
        private string $smtpUsername,
        private string $smtpPassword,
        private string $smtpEncryption,
        private string $from,
        private string $to
    ) {
    }

    /**
     * Sends a summary email using the configured SMTP transport.
     *
     * @param string $subject Email subject.
     * @param string $message Email body.
     *
     * @return void
     *
     * @example
     *  $notifier->send('Cleanup Report', 'Summary text');
     */
    public function send(string $subject, string $message): void
    {
        $dsn = sprintf(
            'smtp://%s:%s@%s:%d?encryption=%s',
            rawurlencode($this->smtpUsername),
            rawurlencode($this->smtpPassword),
            $this->smtpHost,
            $this->smtpPort,
            $this->smtpEncryption
        );

        $mailer = new Mailer(Transport::fromDsn($dsn));

        $email = (new Email())
            ->from($this->from)
            ->to($this->to)
            ->subject($subject)
            ->text($message);

        $mailer->send($email);
    }
}
