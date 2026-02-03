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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Scans filesystem entries under provided root paths.
 */
final class FilesystemScanner
{
    /**
     * @param string[] $rootPaths
     * @param bool $followSymlinks Whether to follow symlinks during scan.
     *
     * @return iterable<SplFileInfo>
     *
     * @example
    *  foreach ($scanner->scan(['/tmp'], false) as $fileInfo) { // ... }
     */
    public function scan(array $rootPaths, bool $followSymlinks): iterable
    {
        foreach ($rootPaths as $rootPath) {
            if (!file_exists($rootPath)) {
                continue;
            }

            yield new SplFileInfo($rootPath);

            if (is_dir($rootPath)) {
                $flags = RecursiveDirectoryIterator::SKIP_DOTS;
                if ($followSymlinks) {
                    $flags |= RecursiveDirectoryIterator::FOLLOW_SYMLINKS;
                }

                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($rootPath, $flags),
                    RecursiveIteratorIterator::CHILD_FIRST
                );

                foreach ($iterator as $fileInfo) {
                    if ($fileInfo instanceof SplFileInfo) {
                        yield $fileInfo;
                    }
                }
            }
        }
    }
}
