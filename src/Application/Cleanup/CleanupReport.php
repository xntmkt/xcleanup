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
 * Renders summary and detail reports for a cleanup run.
 */
final class CleanupReport
{
    /**
     * @param CleanupItem[] $items
     * @param string $confirmKey Confirmation key required for execution.
     * @param DiskUsage $diskUsage Disk usage snapshot before cleanup.
     * @param int $totalSizeBytes Total size of planned items in bytes.
     *
     * @return string Rendered confirmation summary.
     *
     * @example
     *  $summary = $report->renderPlanSummary('abc123', $items, $diskUsage, 2048);
     */
    public function renderPlanSummary(
        string $confirmKey,
        array $items,
        DiskUsage $diskUsage,
        int $totalSizeBytes
    ): string {
        $summary = sprintf(
            "Confirm key: %s\n"
            . "Total files: %d\n"
            . "Total directories: %d\n"
            . "Total size (MB): %.2f\n"
            . "Free before (MB): %.2f\n"
            . "Free after (MB): %.2f\n",
            $confirmKey,
            $this->countType($items, 'file'),
            $this->countType($items, 'dir'),
            $this->bytesToMb($totalSizeBytes),
            $this->bytesToMb($diskUsage->getFreeBytes()),
            $this->bytesToMb($diskUsage->getFreeBytes() + $totalSizeBytes)
        );

        return $summary . "\nPlanned items:\n" . $this->renderItems($items);
    }

    /**
     * @param CleanupItem[] $items
     * @param DiskUsage $diskUsage Disk usage snapshot before cleanup.
     * @param int $totalSizeBytes Total size of deleted items in bytes.
     * @param bool $emergency Whether the run was in emergency mode.
     *
     * @return string Rendered execution summary.
     *
     * @example
     *  $summary = $report->renderExecutionSummary($items, $diskUsage, 2048, false);
     */
    public function renderExecutionSummary(
        array $items,
        DiskUsage $diskUsage,
        int $totalSizeBytes,
        bool $emergency
    ): string {
        return sprintf(
            "Mode: %s\n"
            . "Deleted files: %d\n"
            . "Deleted directories: %d\n"
            . "Total size (MB): %.2f\n"
            . "Free before (MB): %.2f\n"
            . "Free after (MB): %.2f\n",
            $emergency ? 'EMERGENCY' : 'STANDARD',
            $this->countType($items, 'file'),
            $this->countType($items, 'dir'),
            $this->bytesToMb($totalSizeBytes),
            $this->bytesToMb($diskUsage->getFreeBytes()),
            $this->bytesToMb($diskUsage->getFreeBytes() + $totalSizeBytes)
        );
    }

    /**
     * @param CleanupItem[] $items
     *
     * @return string Rendered execution detail report.
     *
     * @example
     *  $detail = $report->renderExecutionDetail($items);
     */
    public function renderExecutionDetail(array $items): string
    {
        return "Deleted items:\n" . $this->renderItems($items);
    }

    /**
     * @param CleanupItem[] $items
     * @param DiskUsage $diskUsage Disk usage snapshot before cleanup.
     * @param int $totalSizeBytes Total size of planned items in bytes.
     * @param bool $emergency Whether the run would be emergency mode.
     *
     * @return string Rendered dry-run summary.
     *
     * @example
     *  $summary = $report->renderDryRunSummary($items, $diskUsage, 2048, false);
     */
    public function renderDryRunSummary(
        array $items,
        DiskUsage $diskUsage,
        int $totalSizeBytes,
        bool $emergency
    ): string {
        return sprintf(
            "Mode: %s (DRY-RUN)\n"
            . "Planned files: %d\n"
            . "Planned directories: %d\n"
            . "Total size (MB): %.2f\n"
            . "Free before (MB): %.2f\n"
            . "Free after (MB): %.2f\n",
            $emergency ? 'EMERGENCY' : 'STANDARD',
            $this->countType($items, 'file'),
            $this->countType($items, 'dir'),
            $this->bytesToMb($totalSizeBytes),
            $this->bytesToMb($diskUsage->getFreeBytes()),
            $this->bytesToMb($diskUsage->getFreeBytes() + $totalSizeBytes)
        );
    }

    /**
     * @param CleanupItem[] $items
     *
     * @return string Rendered dry-run detail report.
     *
     * @example
     *  $detail = $report->renderDryRunDetail($items);
     */
    public function renderDryRunDetail(array $items): string
    {
        return "Planned items (dry-run):\n" . $this->renderItems($items);
    }

    /**
     * @param CleanupItem[] $items
     */
    private function renderItems(array $items): string
    {
        $lines = [];

        foreach ($items as $item) {
            $lines[] = sprintf(
                "%s | %s | %.2f MB",
                $item->getType(),
                $item->getPath(),
                $this->bytesToMb($item->getSizeBytes())
            );
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param CleanupItem[] $items
     */
    private function countType(array $items, string $type): int
    {
        return count(array_filter(
            $items,
            static fn (CleanupItem $item): bool => $item->getType() === $type
        ));
    }

    private function bytesToMb(int $bytes): float
    {
        return $bytes / 1024 / 1024;
    }
}
