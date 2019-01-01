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

        if ($validator instanceof Validator) {
            $this->validator = $validator;
        } else {
            $this->validator = new Validator(Helpers::punyCode());
        }

        if ($resolver instanceof Resolver) {
            $this->resolver = $resolver;
        } else {
            $this->resolver = new Resolver();
        }
    }

    /**
     * @return string
     */
    public function getScheme() : string
    {
        return ($scheme = $this->url->scheme()) ? $scheme : '';
    }

    /**
     * @return string
     */
    public function getUserInfo() : string
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
    public function getAuthority() : string
    {
        return $this->url->authority();
    }

    /**
     * @return string
     */
    public function getHost() : string
    {
        return ($host = $this->url->host()) ? strtolower($host) : '';
    }

    /**
     * @return int|null
     */
    public function getPort()
    {
        $port = $this->url->port();

        if (!$port) {
            return $port;
        }

        $scheme = $this->getScheme();

        if ($scheme) {
            $standardPort = Helpers::getStandardPortByScheme($scheme);

            if ($port === $standardPort) {
                return null;
            }
        }

        return $port;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return ($path = $this->url->path()) ? $path : '';
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return ($query = $this->url->query()) ? $query : '';
    }

    /**
     * @return string
     */
    public function getFragment()
    {
        return ($fragment = $this->url->fragment()) ? $fragment : '';
    }

    /**
     * @param string $scheme
     * @return self
     * @throws \InvalidArgumentException
     * @throws InvalidUrlException
     */
    public function withScheme($scheme) : self
    {
        $newUrl = $this->newUrlInstance();

        if (!$this->validator->scheme($scheme) && trim($scheme) !== '') {
            throw new \InvalidArgumentException('Invalid scheme.');
        }

        $newUrl->scheme($scheme);

        return $this->returnNewInstance($newUrl);
    }

    /**
     * @param string $user
     * @param null|string $password
     * @return self
     * @throws InvalidUrlException
     */
    public function withUserInfo($user, $password = null) : self
    {
        $newUrl = $this->newUrlInstance();
        $newUrl->user($user);
        $newUrl->pass($password);

        return $this->returnNewInstance($newUrl);
    }

    /**
     * @param string $host
     * @return self
     * @throws InvalidUrlException
     */
    public function withHost($host) : self
    {
        $newUrl = $this->newUrlInstance();
        $newUrl->host($host);

        return $this->returnNewInstance($newUrl);
    }

    /**
     * @param int|null $port
     * @return self
     * @throws \InvalidArgumentException
     * @throws InvalidUrlException
     */
    public function withPort($port) : self
    {
        if ($port !== null && $this->validator->port($port) === false) {
            throw new \InvalidArgumentException('Port is outside the valid TCP and UDP port ranges.');
        }

        $newUrl = $this->newUrlInstance();

        if ($port === null) {
            $newUrl->resetPort();
        } else {
            $newUrl->port($port);
        }

        return $this->returnNewInstance($newUrl);
    }

    /**
     * As defined in the interface this method can receive rootless paths, so the provided path will be resolved
     * to an absolute one.
     *
     * @param string $path
     * @return self
     * @throws InvalidUrlException
     */
    public function withPath($path) : self
    {
        $newUrl = $this->newUrlInstance();

        if (!is_string($path)) {
            $path = '';
        }

        if (substr($path, 0, 1) !== '/' && trim($path) !== '') {
            $path = $this->resolver->resolvePath($path, $this->url->path());
        }

        $newUrl->path($path);

        return $this->returnNewInstance($newUrl);
    }

    /**
     * @param string $query
     * @return self
     * @throws InvalidUrlException
     */
    public function withQuery($query) : self
    {
        $newUrl = $this->newUrlInstance();
        $newUrl->query($query);

        return $this->returnNewInstance($newUrl);
    }

    /**
     * @param string $fragment
     * @return self
     * @throws InvalidUrlException
     */
    public function withFragment($fragment) : self
    {
        $newUrl = $this->newUrlInstance();
        $newUrl->fragment($fragment);

        return $this->returnNewInstance($newUrl);
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->url->toString();
    }

    /**
     * @param Url $url
     * @return Uri
     * @throws InvalidUrlException
     */
    private function returnNewInstance(Url $url) : self
    {
        return new self($url, $this->validator, $this->resolver);
    }

    /**
     * @return Url
     * @throws InvalidUrlException
     */
    private function newUrlInstance() : Url
    {
        return new Url($this->url, $this->validator);
    }
}
