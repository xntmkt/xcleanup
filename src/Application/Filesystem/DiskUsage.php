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

namespace XNetVN\Cleanup\Application\Filesystem;

/**
 * Represents disk usage metrics for a filesystem path.
 */
final class DiskUsage
{
    /**
     * Creates a disk usage snapshot.
     *
     * @param int $totalBytes Total bytes on disk.
     * @param int $freeBytes Free bytes on disk.
     *
     * @example
     *  $usage = new DiskUsage(1024, 512);
     */
    public function __construct(
        private int $totalBytes,
        private int $freeBytes
    ) {
    }

    /**
     * Returns total disk size in bytes.
     *
     * @return int
     *
     * @example
     *  $total = $usage->getTotalBytes();
     */
    public function getTotalBytes(): int
    {
        return $this->totalBytes;
    }

    /**
     * Returns free disk space in bytes.
     *
     * @return int
     *
     * @example
     *  $free = $usage->getFreeBytes();
     */
    public function getFreeBytes(): int
    {
        return $this->freeBytes;
    }

    /**
     * Returns free disk space percentage.
     *
     * @return float
     *
     * @example
     *  $percent = $usage->getFreePercent();
     */
    public function getFreePercent(): float
    {
        if ($this->totalBytes === 0) {
            return 0.0;
        }

        return ($this->freeBytes / $this->totalBytes) * 100.0;
    }
}
