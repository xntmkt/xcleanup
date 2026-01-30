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

use XNetVN\Cleanup\Application\Config\ConfigLoader;
use XNetVN\Cleanup\Application\Filesystem\DiskUsage;
use XNetVN\Cleanup\Application\Filesystem\FilesystemScanner;
use XNetVN\Cleanup\Application\Filesystem\PathMatcher;
use XNetVN\Cleanup\Domain\Exception\ConfigurationException;

/**
 * @phpstan-import-type CleanupConfig from ConfigLoader
 */
/**
 * Builds the cleanup plan by scanning files and applying filters.
 */
final class CleanupPlanner
{
    /**
     * Initializes the cleanup planner with filesystem scanning and state tracking.
     *
     * @param FilesystemScanner $scanner Filesystem scanner implementation.
     * @param CleanupStateStore $stateStore State store for deletion history.
     *
     * @example
     *  $planner = new CleanupPlanner($scanner, $stateStore);
     */
    public function __construct(
        private FilesystemScanner $scanner,
        private CleanupStateStore $stateStore
    ) {
    }

    /**
     * @param CleanupConfig $config
     * @param DiskUsage $diskUsage Current disk usage snapshot.
     * @param bool $emergency Whether emergency mode is enabled.
     *
     * @return CleanupPlan
     *
     * @throws ConfigurationException When no absolute root path is provided.
     *
     * @example
     *  $plan = $planner->plan($config, $diskUsage, false);
     */
    public function plan(array $config, DiskUsage $diskUsage, bool $emergency): CleanupPlan
    {
        $allowedPatterns = $config['paths']['allowed_paths'];
        $excludedPatterns = $config['paths']['excluded_paths'];
        $matcher = new PathMatcher($allowedPatterns, $excludedPatterns);

        $rootPaths = $this->resolveRootPaths($allowedPatterns, $emergency, $config);
        $followSymlinks = (bool) $config['paths']['follow_symlinks'];

        $minAgeSeconds = (int) $config['cleanup']['min_age_seconds'];
        $skipWindow = (int) $config['cleanup']['skip_if_deleted_within_seconds'];
        $deleteFiles = (bool) $config['cleanup']['delete_files'];
        $deleteDirs = (bool) $config['cleanup']['delete_empty_directories'];
        $maxItems = (int) $config['cleanup']['max_items'];

        $items = [];
        $now = time();

        foreach ($this->scanner->scan($rootPaths, $followSymlinks) as $fileInfo) {
            $path = $fileInfo->getPathname();

            if ($matcher->isExcluded($path) || !$matcher->isAllowed($path)) {
                continue;
            }

            if ($skipWindow > 0 && $this->stateStore->wasDeletedRecently($path, $skipWindow)) {
                continue;
            }

            $modifiedAt = $fileInfo->getMTime();
            if ($minAgeSeconds > 0 && ($now - $modifiedAt) < $minAgeSeconds) {
                continue;
            }

            if ($fileInfo->isFile()) {
                if (!$deleteFiles) {
                    continue;
                }

                $items[] = new CleanupItem($path, 'file', (int) $fileInfo->getSize(), $modifiedAt);
            }

            if ($fileInfo->isDir()) {
                if (!$deleteDirs) {
                    continue;
                }

                if (!$this->isDirectoryEmpty($path)) {
                    continue;
                }

                $items[] = new CleanupItem($path, 'dir', 0, $modifiedAt);
            }

            if ($maxItems > 0 && count($items) >= $maxItems) {
                break;
            }
        }

        return new CleanupPlan($items, $diskUsage, $emergency);
    }

    /**
     * @param string[] $allowedPatterns
     * @param CleanupConfig $config
     * @return string[]
     */
    private function resolveRootPaths(array $allowedPatterns, bool $emergency, array $config): array
    {
        if ($emergency && is_array($config['emergency']['paths'])) {
            $paths = array_filter($config['emergency']['paths'], 'is_string');
            return $this->filterAbsolutePaths($paths);
        }

        $paths = $this->filterAbsolutePaths($allowedPatterns);

        if ($paths === []) {
            throw new ConfigurationException('At least one absolute allowed path is required for scanning.');
        }

        return $paths;
    }

    /**
     * @param string[] $patterns
     * @return string[]
     */
    private function filterAbsolutePaths(array $patterns): array
    {
        return array_values(array_filter($patterns, function (string $pattern): bool {
            if ($pattern === '' || $this->isRegex($pattern)) {
                return false;
            }

            return str_starts_with($pattern, DIRECTORY_SEPARATOR) && !str_contains($pattern, '*');
        }));
    }

    private function isRegex(string $pattern): bool
    {
        if (strlen($pattern) < 3) {
            return false;
        }

        $delimiter = $pattern[0];
        $last = $pattern[strlen($pattern) - 1];

        return $delimiter === $last && in_array($delimiter, ['#', '/', '~'], true);
    }

    private function isDirectoryEmpty(string $path): bool
    {
        $handle = @opendir($path);
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
}
