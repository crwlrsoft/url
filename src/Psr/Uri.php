<?php

namespace Crwlr\Url\Psr;

use Crwlr\Url\Exceptions\InvalidUrlException;
use Crwlr\Url\Helpers;
use Crwlr\Url\Resolver;
use Crwlr\Url\Url;
use Crwlr\Url\Validator;
use Psr\Http\Message\UriInterface;

/**
 * Class Uri
 *
 * This is an adapter class that implements the PSR-7 UriInterface that can't be implemented
 * by the Url class itself because it isn't designed to be immutable.
 */

class Uri implements UriInterface
{
    /**
     * @var Url
     */
    private $url;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @param string $url
     * @param Validator|null $validator
     * @param Resolver|null $resolver
     * @throws InvalidUrlException
     */
    public function __construct($url, $validator = null, $resolver = null)
    {
        if ($url instanceof Url) {
            $this->url = $url;
        } elseif (is_string($url)) {
            $this->url = new Url($url, $validator);
        } else {
            throw new InvalidUrlException('Param url must be either a string or an instance of Crwlr\Url\Url.');
        }

        $this->validator = $validator instanceof Validator ? $validator : new Validator(Helpers::punyCode());
        $this->resolver = $resolver instanceof Resolver ? $resolver : new Resolver();
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->url->scheme() ?: '';
    }

    /**
     * @return string
     */
    public function getUserInfo(): string
    {
        $userInfo = '';

        if ($this->url->user()) {
            $userInfo = $this->url->user();

            if ($this->url->password()) {
                $userInfo .= ':' . $this->url->password();
            }
        }

        return $userInfo;
    }

    /**
     * @return string
     */
    public function getAuthority(): string
    {
        return $this->url->authority();
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return ($host = $this->url->host()) ? strtolower($host) : '';
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->url->port();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->url->path() ?: '';
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->url->query() ?: '';
    }

    /**
     * @return string
     */
    public function getFragment(): string
    {
        return $this->url->fragment() ?: '';
    }

    /**
     * @param string $scheme
     * @return Uri
     * @throws \InvalidArgumentException
     * @throws InvalidUrlException
     */
    public function withScheme($scheme): Uri
    {
        if (!is_string($scheme) || (!$this->validator->scheme($scheme) && trim($scheme) !== '')) {
            throw new \InvalidArgumentException('Invalid scheme.');
        }

        return $this->newInstance($this->newUrlInstance()->scheme($scheme));
    }

    /**
     * @param string $user
     * @param null|string $password
     * @return Uri
     * @throws InvalidUrlException
     */
    public function withUserInfo($user, $password = null): Uri
    {
        $newUrl = $this->newUrlInstance();
        $newUrl->user($user);
        $newUrl->pass($password);

        return $this->newInstance($newUrl);
    }

    /**
     * @param string $host
     * @return Uri
     * @throws InvalidUrlException
     */
    public function withHost($host): Uri
    {
        $newUrl = $this->newUrlInstance();
        $newUrl->host($host);

        return $this->newInstance($newUrl);
    }

    /**
     * @param int|null $port
     * @return Uri
     * @throws \InvalidArgumentException
     * @throws InvalidUrlException
     */
    public function withPort($port): Uri
    {
        if ($port !== null && $this->validator->port($port) === null) {
            throw new \InvalidArgumentException('Port is outside the valid TCP and UDP port ranges.');
        }

        $newUrl = $this->newUrlInstance();

        if ($port === null) {
            $newUrl->resetPort();
        } else {
            $newUrl->port($port);
        }

        return $this->newInstance($newUrl);
    }

    /**
     * As defined in the interface this method can receive rootless paths, so the provided path will be resolved
     * to an absolute one.
     *
     * @param string $path
     * @return Uri
     * @throws InvalidUrlException
     */
    public function withPath($path): Uri
    {
        $newUrl = $this->newUrlInstance();

        if (!is_string($path)) {
            $path = '';
        }

        if (substr($path, 0, 1) !== '/' && trim($path) !== '') {
            $path = $this->resolver->resolvePath($path, $this->url->path());
        }

        $newUrl->path($path);

        return $this->newInstance($newUrl);
    }

    /**
     * @param string $query
     * @return Uri
     * @throws InvalidUrlException
     */
    public function withQuery($query): Uri
    {
        $newUrl = $this->newUrlInstance();
        $newUrl->query($query);

        return $this->newInstance($newUrl);
    }

    /**
     * @param string $fragment
     * @return Uri
     * @throws InvalidUrlException
     */
    public function withFragment($fragment): Uri
    {
        $newUrl = $this->newUrlInstance();
        $newUrl->fragment($fragment);

        return $this->newInstance($newUrl);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->url->toString();
    }

    /**
     * @param Url $url
     * @return Uri
     * @throws InvalidUrlException
     */
    private function newInstance(Url $url): Uri
    {
        return new self($url, $this->validator, $this->resolver);
    }

    /**
     * @return Url
     * @throws InvalidUrlException
     */
    private function newUrlInstance(): Url
    {
        return new Url($this->url, $this->validator);
    }
}
