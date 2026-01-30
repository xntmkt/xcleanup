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

use XNetVN\Cleanup\Domain\Exception\FilesystemException;

/**
 * Reads disk usage metrics from the underlying filesystem.
 */
final class DiskUsageReader
{
    /**
     * Reads disk usage for the provided path.
     *
     * @param string $path Filesystem path to inspect.
     *
     * @return DiskUsage
     *
     * @throws FilesystemException When disk metrics cannot be read.
     *
     * @example
     *  $usage = $reader->read('/');
     */
    public function read(string $path): DiskUsage
    {
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false) {
            throw new FilesystemException(sprintf('Unable to read disk usage for path: %s', $path));
        }

        return new DiskUsage((int) $total, (int) $free);
    }
}
