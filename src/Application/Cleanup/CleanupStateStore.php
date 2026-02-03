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

use XNetVN\Cleanup\Domain\Exception\FilesystemException;

/**
 * Tracks recently deleted items to avoid repeated deletions.
 */
final class CleanupStateStore
{
    /** @var array<string, int> */
    private array $state = [];

    /**
     * Initializes the state store from the given state file path.
     *
     * @param string $stateFile Absolute path to the state file.
     *
     * @throws FilesystemException When the state file cannot be read.
     *
     * @example
     *  $store = new CleanupStateStore('/var/log/cleanup/state.json');
     */
    public function __construct(private string $stateFile)
    {
        $this->load();
    }

    /**
     * Checks whether a path was deleted within the given time window.
     *
     * @param string $path Absolute path.
     * @param int $windowSeconds Age window in seconds.
     *
     * @return bool
     *
     * @example
    *  if ($store->wasDeletedRecently($path, 3600)) { // ... }
     */
    public function wasDeletedRecently(string $path, int $windowSeconds): bool
    {
        if (!isset($this->state[$path])) {
            return false;
        }

        return (time() - $this->state[$path]) <= $windowSeconds;
    }

    /**
     * @param CleanupItem[] $items
     *
     * @return void
     *
     * @throws FilesystemException When the state file cannot be written.
     *
     * @example
     *  $store->recordDeleted($items);
     */
    public function recordDeleted(array $items): void
    {
        $timestamp = time();

        foreach ($items as $item) {
            $this->state[$item->getPath()] = $timestamp;
        }

        $this->persist();
    }

    private function load(): void
    {
        if (!is_file($this->stateFile)) {
            return;
        }

        $contents = file_get_contents($this->stateFile);
        if ($contents === false) {
            throw new FilesystemException(sprintf('Unable to read state file: %s', $this->stateFile));
        }

        $decoded = json_decode($contents, true);
        if (is_array($decoded)) {
            $this->state = array_map('intval', $decoded);
        }
    }

    private function persist(): void
    {
        $directory = dirname($this->stateFile);
        if (!is_dir($directory)) {
            if (!@mkdir($directory, 0750, true) && !is_dir($directory)) {
                throw new FilesystemException(sprintf('Unable to create state directory: %s', $directory));
            }
        }

        $encoded = json_encode($this->state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        $lockHandle = fopen($this->stateFile . '.lock', 'c');
        if ($lockHandle === false) {
            throw new FilesystemException(sprintf('Unable to open state lock file: %s.lock', $this->stateFile));
        }

        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            throw new FilesystemException(sprintf('Unable to acquire state file lock: %s', $this->stateFile));
        }

        $tmpPath = $this->stateFile . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($tmpPath, $encoded) === false) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            throw new FilesystemException(sprintf('Unable to write temporary state file: %s', $tmpPath));
        }

        if (!rename($tmpPath, $this->stateFile)) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            throw new FilesystemException(sprintf('Unable to replace state file: %s', $this->stateFile));
        }

        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}
