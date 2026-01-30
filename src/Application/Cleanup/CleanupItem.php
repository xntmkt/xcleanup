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

/**
 * Represents a file or directory candidate for cleanup.
 */
final class CleanupItem
{
    /**
     * Initializes a cleanup candidate.
     *
     * @param string $path Absolute path of the item.
     * @param string $type Item type (`file` or `dir`).
     * @param int $sizeBytes Size in bytes.
     * @param int $modifiedAt Unix timestamp of last modification.
     *
     * @example
     *  $item = new CleanupItem('/tmp/a.log', 'file', 1024, time());
     */
    public function __construct(
        private string $path,
        private string $type,
        private int $sizeBytes,
        private int $modifiedAt
    ) {
    }

    /**
     * Returns the absolute path of the cleanup item.
     *
     * @return string
     *
     * @example
     *  $path = $item->getPath();
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns the item type (`file` or `dir`).
     *
     * @return string
     *
     * @example
     *  $type = $item->getType();
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the item size in bytes.
     *
     * @return int
     *
     * @example
     *  $size = $item->getSizeBytes();
     */
    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    /**
     * Returns the modification timestamp.
     *
     * @return int Unix timestamp.
     *
     * @example
     *  $modifiedAt = $item->getModifiedAt();
     */
    public function getModifiedAt(): int
    {
        return $this->modifiedAt;
    }
}
