<?php

namespace Crwlr\Url;

use Crwlr\Url\Exceptions\InvalidUrlException;
use Exception;

/**
 * Class Resolver
 *
 * This class handles resolving a relative URL to an absolute one given the URL where the relative one was found.
 */

class Resolver
{
    /**
     * Resolve any relative reference to an absolute URL against a base URL.
     *
     * Example:
     * Base: https://www.example.com/foo/bar/baz
     * <a href="../link"> => ../link resolves to https://www.example.com/foo/link
     *
     * @param string $subject
     * @param Url $base
     * @return Url
     * @throws InvalidUrlException|Exception
     */
    public function resolve(string $subject, Url $base): Url
    {
        $absoluteUrl = Validator::absoluteUrl($subject);

        if ($absoluteUrl) {
            return new Url($absoluteUrl);
        }

        $firstChar = substr($subject, 0, 1);

        if ($firstChar === '#' || $firstChar === '?') {
            return new Url($base->root() . $base->path() . $subject);
        }

        $subject = $this->resolveDots($subject, $base->path() ?? '/');

        if (str_starts_with($subject, '//')) {
            return new Url($base->scheme() . ':' . $subject);
        }

        return new Url($base->root() . $subject);
    }

    /**
     * Resolve a relative reference against a base path.
     */
    public function resolvePath(string $resolvePath, string $basePath): string
    {
        return $this->resolveDots($resolvePath, $basePath);
    }

    /**
     * Resolve all . in the subject path with the base path.
     *
     * Example:
     * subject: ./foo/../bar/./baz
     * base path: /one/two/three
     * result: /one/two/bar/baz
     */
    private function resolveDots(string $subject = '', string $basePath = ''): string
    {
        $basePathDir = $this->getDirectoryPath($basePath);
        $splitBySlash = explode('/', $subject);

        foreach ($splitBySlash as $key => $part) {
            if ($part === '.') {
                unset($splitBySlash[$key]);
            } elseif ($part === '..') {
                $parentDirKey = $this->getParentDirFromArray($splitBySlash, $key);

                if ($parentDirKey === null) {
                    $basePathDir = $this->getParentDirectoryPath($basePathDir);
                    unset($splitBySlash[$key]);
                } else {
                    unset($splitBySlash[$parentDirKey], $splitBySlash[$key]);
                }
            }
        }

        if (str_starts_with($subject, '/')) {
            $resolvedPath = implode('/', $splitBySlash);
        } else {
            $resolvedPath = $basePathDir . implode('/', $splitBySlash);
        }

        if (
            (str_ends_with($subject, '/.') || str_ends_with($subject, '/..')) &&
            !str_ends_with($resolvedPath, '/')
        ) {
            $resolvedPath .= '/';
        }

        return $resolvedPath;
    }

    /**
     * Helper method for resolveDots
     *
     * @param string[] $splitPath
     */
    private function getParentDirFromArray(array $splitPath, int $currentKey = 0): ?int
    {
        if ($currentKey === 0) {
            return null;
        }

        for ($i = ($currentKey - 1); $i >= 0; $i--) {
            if (isset($splitPath[$i]) && !empty($splitPath[$i])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Get the path to parent directory of a path.
     */
    private function getParentDirectoryPath(string $path = ''): string
    {
        if (!str_ends_with($path, '/')) {
            $path = $this->getDirectoryPath($path);
        }

        if ($path === '/') {
            return $path;
        }

        $path = Helpers::stripFromEnd($path, '/');
        $splitBySlash = explode('/', $path);

        return Helpers::stripFromEnd($path, end($splitBySlash));
    }

    /**
     * Returns the $path until the last slash.
     *
     * /foo/bar => /foo/
     */
    private function getDirectoryPath(string $path = ''): string
    {
        if (str_ends_with($path, '/')) {
            return $path;
        }

        $splitBySlash = explode('/', $path);

        return Helpers::stripFromEnd($path, end($splitBySlash));
    }
}
