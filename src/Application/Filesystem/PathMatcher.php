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
 * Matches filesystem paths against allowed and excluded patterns.
 */
final class PathMatcher
{
    /**
     * @param string[] $allowedPatterns
     * @param string[] $excludedPatterns
     *
     * @example
     *  $matcher = new PathMatcher(['/tmp'], ['/tmp/keep']);
     */
    public function __construct(
        private array $allowedPatterns,
        private array $excludedPatterns
    ) {
    }

    /**
     * Checks whether the path matches any allowed pattern.
     *
     * @param string $path Absolute path to evaluate.
     *
     * @return bool
     *
     * @example
     *  if ($matcher->isAllowed('/tmp/file')) { /* ... */ }
     */
    public function isAllowed(string $path): bool
    {
        return $this->matchesAny($path, $this->allowedPatterns);
    }

    /**
     * Checks whether the path matches any excluded pattern.
     *
     * @param string $path Absolute path to evaluate.
     *
     * @return bool
     *
     * @example
     *  if ($matcher->isExcluded('/tmp/keep/file')) { /* ... */ }
     */
    public function isExcluded(string $path): bool
    {
        return $this->matchesAny($path, $this->excludedPatterns);
    }

    /**
     * @param string[] $patterns
     */
    private function matchesAny(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->isRegex($pattern)) {
                if (preg_match($pattern, $path) === 1) {
                    return true;
                }

                continue;
            }

            $normalized = rtrim($pattern, DIRECTORY_SEPARATOR);
            if ($normalized === '') {
                continue;
            }

            if ($path === $normalized || str_starts_with($path, $normalized . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
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
}
