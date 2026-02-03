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

/**
 * Executes cleanup operations and returns the execution result.
 */
final class CleanupExecutor
{
    /**
     * Creates a cleanup executor with a shared state store.
     *
     * @param CleanupStateStore $stateStore State store used to track deletions.
     *
     * @example
     *  $executor = new CleanupExecutor($stateStore);
     */
    public function __construct(private CleanupStateStore $stateStore)
    {
    }

    /**
     * Executes the cleanup plan and returns the result.
     *
     * @param CleanupPlan $plan Cleanup plan to execute.
     * @param LoggerInterface $logger Logger for audit messages.
     *
     * @return CleanupResult Execution result containing deleted and failed items.
     *
     * @example
     *  $result = $executor->execute($plan, $logger);
     */
    public function execute(CleanupPlan $plan, LoggerInterface $logger): CleanupResult
    {
        $deleted = [];
        $failed = [];

        foreach ($plan->getItems() as $item) {
            $path = $item->getPath();

            if ($item->getType() === 'file') {
                if (unlink($path)) {
                    $deleted[] = $item;
                    $logger->info('Deleted file', ['path' => $path]);
                } else {
                    $failed[] = $item;
                    $logger->warning('Failed to delete file', [
                        'path' => $path,
                        'error' => $this->getLastErrorMessage(),
                    ]);
                }

                continue;
            }

            if ($item->getType() === 'dir') {
                if ($this->isDirectoryEmpty($path) && rmdir($path)) {
                    $deleted[] = $item;
                    $logger->info('Deleted directory', ['path' => $path]);
                } else {
                    $failed[] = $item;
                    $logger->warning('Failed to delete directory', [
                        'path' => $path,
                        'error' => $this->getLastErrorMessage(),
                    ]);
                }
            }
        }

        if ($deleted !== []) {
            $this->stateStore->recordDeleted($deleted);
        }

        return new CleanupResult($deleted, $failed);
    }

    private function isDirectoryEmpty(string $path): bool
    {
        $handle = opendir($path);
        if ($handle === false) {
            return false;
        }

        while (($entry = readdir($handle)) !== false) {
            if ($entry !== '.' && $entry !== '..') {
                closedir($handle);
                return false;
            }
        }

        closedir($handle);
        return true;
    }

    private function getLastErrorMessage(): string
    {
        $error = error_get_last();
        if (is_array($error)) {
            return $error['message'];
        }

        return 'Unknown filesystem error.';
    }
}
