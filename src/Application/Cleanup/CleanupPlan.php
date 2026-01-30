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

use XNetVN\Cleanup\Application\Filesystem\DiskUsage;

/**
 * Represents the planned cleanup operations.
 */
final class CleanupPlan
{
    /**
     * @param CleanupItem[] $items
     * @param DiskUsage $diskUsage Disk usage snapshot before cleanup.
     * @param bool $emergency Whether the plan is in emergency mode.
     *
     * @example
     *  $plan = new CleanupPlan($items, $diskUsage, false);
     */
    public function __construct(
        private array $items,
        private DiskUsage $diskUsage,
        private bool $emergency
    ) {
    }

    /**
     * @return CleanupItem[]
     *
     * @example
     *  $items = $plan->getItems();
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Returns the disk usage snapshot used for planning.
     *
     * @return DiskUsage
     *
     * @example
     *  $diskUsage = $plan->getDiskUsage();
     */
    public function getDiskUsage(): DiskUsage
    {
        return $this->diskUsage;
    }

    /**
     * Indicates whether emergency cleanup is enabled for this plan.
     *
     * @return bool
     *
     * @example
     *  if ($plan->isEmergency()) { /* ... */ }
     */
    public function isEmergency(): bool
    {
        return $this->emergency;
    }

    /**
     * Computes the total size of all items in bytes.
     *
     * @return int
     *
     * @example
     *  $totalBytes = $plan->getTotalSizeBytes();
     */
    public function getTotalSizeBytes(): int
    {
        return array_reduce(
            $this->items,
            static fn (int $carry, CleanupItem $item): int => $carry + $item->getSizeBytes(),
            0
        );
    }

    /**
     * Returns the number of file items in the plan.
     *
     * @return int
     *
     * @example
     *  $fileCount = $plan->getFileCount();
     */
    public function getFileCount(): int
    {
        return count(array_filter(
            $this->items,
            static fn (CleanupItem $item): bool => $item->getType() === 'file'
        ));
    }

    /**
     * Returns the number of directory items in the plan.
     *
     * @return int
     *
     * @example
     *  $dirCount = $plan->getDirectoryCount();
     */
    public function getDirectoryCount(): int
    {
        return count(array_filter(
            $this->items,
            static fn (CleanupItem $item): bool => $item->getType() === 'dir'
        ));
    }
}
