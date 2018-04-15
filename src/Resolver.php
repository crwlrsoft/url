<?php

namespace Crwlr\Url;

use Crwlr\Url\Exceptions\InvalidUrlException;

/**
 * Class Resolver
 *
 * This class handles resolving a relative url to an absolute one given the url where the relative one was found.
 */

class Resolver
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param Validator|null $validator
     */
    public function __construct(Validator $validator = null)
    {
        $this->validator = ($validator instanceof Validator) ? $validator : new Validator();
    }

    /**
     * Resolve any relative url you may find on a website to an absolute url with the base url of the
     * document where the relative url was found.
     * e.g.:
     * https://www.example.com/foo/bar/baz
     * <a href="../link"> => ../link resolves to https://www.example.com/foo/link
     *
     * @param string $subject
     * @param Url $base
     * @return Url
     * @throws InvalidUrlException
     */
    public function resolve(string $subject = '', Url $base) : Url
    {
        try {
            $subject = trim(str_replace(' ', '%20', $subject));
            $validUrl = $this->validator->url($subject);
            return new Url($validUrl); // If subject is not a relative url but a full valid url, return it immediately.
        } catch (InvalidUrlException $e) { }

        $firstChar = substr($subject, 0, 1);

        if ($firstChar === '#' || $firstChar === '?') {
            return new Url($base->root() . $base->path() . $subject);
        }

        $subject = $this->resolveDots($subject, $base->path());

        if (substr($subject, 0, 2) === '//') {
            return new Url($base->scheme() . ':' . $subject);
        }

        return new Url($base->root() . $subject);
    }

    /**
     * Resolve all . in the subject path with the base path.
     * e.g.:
     * subject: ./foo/../bar/./baz
     * base path: /one/two/three
     * result: /one/two/bar/baz
     *
     * @param string $subject
     * @param string $basePath
     * @return string
     */
    private function resolveDots(string $subject = '', string $basePath = '') : string
    {
        $basePathDir = $this->getDirectoryPath($basePath);
        $splitBySlash = explode('/', $subject);

        foreach ($splitBySlash as $key => $part) {
            if ($part === '.') {
                unset($splitBySlash[$key]);
            } elseif ($part === '..') {
                $parentDirKey = $this->getParentDirFromArray($splitBySlash, $key);

                if ($parentDirKey !== false) {
                    unset($splitBySlash[$parentDirKey], $splitBySlash[$key]);
                } else {
                    $basePathDir = $this->getParentDirectoryPath($basePathDir);
                    unset($splitBySlash[$key]);
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
     * @param array $splitPath
     * @param int $currentKey
     * @return bool|int
     */
    private function getParentDirFromArray(array $splitPath = [], int $currentKey = 0)
    {
        if ($currentKey === 0) {
            return false;
        }

        for ($i = ($currentKey - 1); $i >= 0; $i--) {
            if (isset($splitPath[$i]) && !empty($splitPath[$i])) {
                return $i;
            }
        }

        return false;
    }

    /**
     * Get the path to parent directory of a path.
     *
     * @param string $path
     * @return string
     */
    private function getParentDirectoryPath(string $path = '') : string
    {
        if (substr($path, -1, 1) !== '/') {
            $path = $this->getDirectoryPath($path);
        }

        if ($path === '/') {
            return $path;
        }

        $path = Parser::stripFromEnd($path, '/');
        $splitBySlash = explode('/', $path);

        return Parser::stripFromEnd($path, end($splitBySlash));
    }

    /**
     * Returns the $path until the last /
     * e.g. /foo/bar => /foo/
     *
     * @param string $path
     * @return string
     */
    private function getDirectoryPath(string $path = '') : string
    {
        if (substr($path, -1, 1) === '/') {
            return $path;
        }

        $splitBySlash = explode('/', $path);

        return Parser::stripFromEnd($path, end($splitBySlash));
    }
}
