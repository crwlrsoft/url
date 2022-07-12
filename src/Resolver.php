<?php

namespace Crwlr\Url;

use Crwlr\Url\Exceptions\InvalidUrlException;

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
     * @throws InvalidUrlException
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

        if (substr($subject, 0, 2) === '//') {
            return new Url($base->scheme() . ':' . $subject);
        }

        return new Url($base->root() . $subject);
    }

    /**
     * Resolve a relative reference against a base path.
     *
     * @param string $resolvePath
     * @param string $basePath
     * @return string
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
     *
     * @param string $subject
     * @param string $basePath
     * @return string
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

        if (substr($subject, 0, 1) === '/') {
            $resolvedPath = implode('/', $splitBySlash);
        } else {
            $resolvedPath = $basePathDir . implode('/', $splitBySlash);
        }

        if (substr($subject, -2) === '/.' || substr($subject, -3) === '/..') {
            $resolvedPath .= '/';
        }

        return $resolvedPath;
    }

    /**
     * Helper method for resolveDots
     *
     * @param array|string[] $splitPath
     * @param int $currentKey
     * @return null|int
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
     *
     * @param string $path
     * @return string
     */
    private function getParentDirectoryPath(string $path = ''): string
    {
        if (substr($path, -1, 1) !== '/') {
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
     *
     * @param string $path
     * @return string
     */
    private function getDirectoryPath(string $path = ''): string
    {
        if (substr($path, -1, 1) === '/') {
            return $path;
        }

        $splitBySlash = explode('/', $path);

        return Helpers::stripFromEnd($path, end($splitBySlash));
    }
}
