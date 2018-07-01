<?php

namespace Crwlr\Url;

use Crwlr\Url\Exceptions\InvalidUrlException;
use TrueBV\Punycode;

/**
 * Class Url
 *
 * This class is the central unit of this package. It represents a url, gives access to its components and also
 * to some functionality that is implemented in other classes like resolving relative to absolute urls (class Resolver).
 * As it has a __toString method it can also be used like a string.
 *
 * For performance reasons the actual parsing of url components is deferred until some component is queried,
 * especially the parts of the host (registrable domain, domain suffix, subdomain), because therefore it's perhaps
 * necessary to load the full public suffix list which is pretty big.
 */

class Url
{
    /**
     * All components of the url.
     *
     * @var string
     */
    private $url, $scheme, $user, $pass, $host, $domain, $domainSuffix, $subdomain, $port, $path, $query, $fragment;

    /**
     * Again all components including alias method names.
     * Used to verify if a private property can be accessed via magic __get() and __set().
     *
     * @var array
     */
    private $components = [
        'scheme',
        'user',
        'pass',
        'password',
        'host',
        'domain',
        'domainLabel',
        'domainSuffix',
        'subdomain',
        'port',
        'path',
        'query',
        'queryArray',
        'fragment',
        'root',
        'relative',
    ];

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var bool
     */
    private $isInitialized = false;

    /**
     * @param string $url
     * @throws InvalidUrlException
     */
    public function __construct(string $url = '')
    {
        $punyCode = new Punycode();
        $suffixes = new Suffixes($punyCode);
        $this->validator = new Validator($suffixes, null, $punyCode);
        $this->parser = new Parser($suffixes);
        $this->url = $this->validator->url($url);
    }

    /**
     * @param string $url
     * @return Url
     * @throws InvalidUrlException
     */
    public static function parse(string $url = '')
    {
        return new self($url);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if (in_array($name, $this->components)) {
            return $this->$name();
        }

        return null;
    }

    /**
     * @param string $name
     * @param string|int|array $value
     * @return mixed
     */
    public function __set(string $name, $value)
    {
        if (in_array($name, $this->components)) {
            return $this->$name($value);
        }

        return null;
    }

    /**
     * If param $scheme is null this method will return the current scheme.
     * If $scheme is a string that's a valid url scheme, it will replace the current scheme.
     * When $scheme is an empty string the current scheme will be reset to no scheme.
     *
     * @param null|string $scheme
     * @return string|null|Url
     */
    public function scheme(string $scheme = null)
    {
        $this->init();

        if ($scheme === null) {
            return $this->scheme;
        }

        $validScheme = $this->validator->scheme($scheme);

        if ($validScheme) {
            $this->scheme = $validScheme;
            $this->updateFullUrl();
        } elseif (trim($scheme) === '') {
            $this->scheme = null;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getScheme() : string
    {
        return ($scheme = $this->scheme()) ? $scheme : '';
    }

    /**
     * @param string $scheme
     * @return $this|static
     */
    public function withScheme($scheme)
    {
        $this->scheme($scheme);

        return $this;
    }

    /**
     * @param null|string|int $user
     * @return string|null|Url
     */
    public function user($user = null)
    {
        $this->init();

        if ($user === null) {
            return $this->user;
        }

        $validUser = $this->validator->userOrPassword($user);

        if ($validUser) {
            $this->user = $validUser;
            $this->updateFullUrl();
        } elseif (trim($user) === '') {
            $this->user = null;
            $this->pass = null;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * @param null|string|int $password
     * @return string|null|Url
     */
    public function password($password = null)
    {
        $this->init();

        if ($password === null) {
            return $this->pass;
        }

        $validPassword = $this->validator->userOrPassword($password);

        if ($validPassword) {
            $this->pass = $validPassword;
            $this->updateFullUrl();
        } elseif (trim($password) === '') {
            $this->pass = null;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * Alias for method password().
     *
     * @param null|string|int $pass
     * @return string|null|Url
     */
    public function pass($pass = null)
    {
        return $this->password($pass);
    }

    /**
     * @return string
     */
    public function getUserInfo() : string
    {
        $userInfo = '';

        if ($this->user()) {
            $userInfo = $this->user();

            if ($this->password()) {
                $userInfo .= ':' . $this->password();
            }
        }

        return $userInfo;
    }

    /**
     * @param string $user
     * @param null|string $password
     * @return $this|static
     */
    public function withUserInfo($user, $password = null)
    {
        $this->user($user);

        if ($password !== null) {
            $this->pass($password);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthority() : string
    {
        if (empty($this->host())) {
            return '';
        }

        $authority = '';

        if (!empty($this->getUserInfo())) {
            $authority = $this->getUserInfo() . '@';
        }

        $authority .= $this->host();

        if ($this->port()) {
            $authority .= ':' . $this->port();
        }

        return $authority;
    }

    /**
     * @param null|string $host
     * @return string|null|Url
     */
    public function host($host = null)
    {
        $this->init();

        if ($host === null) {
            return $this->host;
        }

        $validHost = $this->validator->host($host);

        if ($validHost) {
            $this->replaceHost($validHost);
            $this->updateFullUrl();
        } elseif (trim($host) === '') {
            $this->host = $this->domain = $this->domainSuffix = $this->subdomain = null;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getHost() : string
    {
        return ($host = $this->host()) ? strtolower($host) : '';
    }

    /**
     * @param string $host
     * @return $this|static
     */
    public function withHost($host) : Url
    {
        $this->host($host);

        return $this;
    }

    /**
     * The registrable domain, but as this would be very long to type it's just called domain.
     *
     * @param null|string $domain
     * @return string|null|Url
     */
    public function domain(string $domain = null)
    {
        $this->init();

        if ($domain === null) {
            if ($this->domain === null && $this->hasHost()) {
                $this->domain = $this->parser->getDomainFromHost($this->host, $this->domainSuffix());
            }

            return $this->domain;
        }

        if ($this->hasDomain()) {
            $domain = $this->validator->domain($domain);

            if ($domain) {
                $this->replaceDomain($domain);
                $this->updateHost();
                $this->updateFullUrl();
            }
        }

        return $this;
    }

    /**
     * @param null|string|int $domainLabel
     * @return string|null|Url
     */
    public function domainLabel($domainLabel = null)
    {
        if ($domainLabel === null) {
            $domain = $this->domain();

            if ($domain) {
                return Parser::stripFromEnd($domain, '.' . $this->domainSuffix());
            }

            return null;
        }

        if ($this->hasDomain()) {
            $domainLabel = $this->validator->domain($domainLabel, true);

            if ($domainLabel) {
                $this->replaceDomain($domainLabel, true);
                $this->updateHost();
                $this->updateFullUrl();
            }
        }

        return $this;
    }

    /**
     * @param null|string $domainSuffix
     * @return string|null|Url
     */
    public function domainSuffix(string $domainSuffix = null)
    {
        $this->init();

        if ($domainSuffix === null) {
            if ($this->domainSuffix === null && $this->hasHost()) {
                $this->domainSuffix = $this->parser->getDomainSuffixFromHost($this->host);
            }

            return $this->domainSuffix;
        }

        // You can't replace the suffix in a url that doesn't contain a registrable domain.
        if ($this->hasDomain()) {
            $domainSuffix = $this->validator->domainSuffix($domainSuffix);

            if ($domainSuffix) {
                $this->replaceDomainSuffix($domainSuffix);
                $this->updateHost();
                $this->updateFullUrl();
            }
        }

        return $this;
    }

    /**
     * @param null|string|int $subdomain
     * @return string|null|Url
     */
    public function subdomain($subdomain = null)
    {
        $this->init();

        if ($subdomain === null) {
            if ($this->subdomain === null && $this->hasHost()) {
                $this->subdomain = $this->parser->getSubdomainFromHost($this->host, $this->domain());
            }

            return $this->subdomain;
        }

        $subdomain = $this->validator->subdomain($subdomain);

        if ($subdomain) {
            $this->subdomain = $subdomain;
            $this->updateHost();
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * @param null|int|string $port
     * @return int|null|Url
     */
    public function port($port = null)
    {
        $this->init();

        if ($port === null) {
            return $this->port;
        }

        $port = $this->validator->port($port);

        if ($port !== false) {
            $this->port = $port;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * @param null|string $path
     * @return string|null|Url
     */
    public function path(string $path = null)
    {
        $this->init();

        if ($path === null) {
            return $this->path;
        }

        $path = $this->validator->path($path);

        if ($path) {
            $this->path = $path;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * @param null|string|array $query
     * @return string|null|Url
     */
    public function query($query = null)
    {
        $this->init();

        if ($query === null) {
            return $this->query;
        }

        $query = $this->validator->query($query);

        if ($query) {
            $this->query = $query;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * @param null|array $query
     * @return array|Url
     */
    public function queryArray(array $query = null)
    {
        $this->init();

        if ($query === null) {
            if (!$this->query) {
                return [];
            }

            return $this->parser->queryStringToArray($this->query);
        } elseif (is_array($query)) {
            $query = $this->validator->query(http_build_query($query));

            if ($query) {
                $this->query = $query;
                $this->updateFullUrl();
            }
        }

        return $this;
    }

    /**
     * @param null|string $fragment
     * @return string|null|Url
     */
    public function fragment(string $fragment = null)
    {
        $this->init();

        if ($fragment === null) {
            return $this->fragment;
        }

        $fragment = $this->validator->fragment($fragment);

        if ($fragment) {
            $this->fragment = $fragment;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * Get the root url. e.g.:
     * Full url: https://www.example.com/path?query=string
     * Root url: https://www.example.com
     *
     * @return string
     */
    public function root() : string
    {
        $this->init();

        $root = $this->scheme() . ':';

        if ($this->hasHost()) {
            $root .= '//';

            if ($this->user()) {
                $root .= $this->user();

                if ($this->password()) {
                    $root .= ':' . $this->password();
                }

                $root .= '@';
            }

            $root .= $this->host();

            if ($this->port()) {
                $root .= ':' . $this->port();
            }
        }

        return $root;
    }

    /**
     * Returns the path, query and fragment components of the url combined, like a relative url.
     *
     * @return string
     */
    public function relative() : string
    {
        $this->init();

        if ($this->path()) {
            $relative = $this->path();
        } else {
            $relative = '/';
        }

        if ($this->query()) {
            $relative .= '?' . $this->query();
        }

        if ($this->fragment()) {
            $relative .= '#' . $this->fragment();
        }

        return $relative;
    }

    /**
     * @param string $relativeUrl
     * @return Url
     * @throws InvalidUrlException
     */
    public function resolve(string $relativeUrl = '') : Url
    {
        if (!$this->resolver) {
            $this->resolver = new Resolver($this->validator);
        }

        return $this->resolver->resolve($relativeUrl, $this);
    }

    /**
     * @param Url|string $compareWith
     * @param string $compareWhat
     * @return bool
     */
    public function compare($compareWith, string $compareWhat) : bool
    {
        if (is_string($compareWith)) {
            try {
                $compareWith = new Url($compareWith);
            } catch (\Exception $exception) {
                return false;
            }
        } elseif (!$compareWith instanceof Url) {
            return false;
        }

        if (in_array($compareWhat, $this->components)) {
            return ($this->{$compareWhat}() === $compareWith->{$compareWhat}());
        }

        return false;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString() : string
    {
        return $this->url;
    }

    /**
     * Parse the url provided in the constructor and set the parsed properties.
     * Validating the url already happened in the constructor, so there should be no problem parsing it.
     */
    private function init()
    {
        if ($this->isInitialized === false) {
            foreach ($this->parser->parse($this->url) as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->{$property} = $value;
                }
            }

            $this->isInitialized = true;
        }
    }

    /**
     * Regenerate the full url after changing components.
     */
    private function updateFullUrl()
    {
        $this->url = $this->root() . $this->relative();
    }

    /**
     * After changing a part of the host (subdomain, domain, suffix), regenerate the full host string.
     */
    private function updateHost()
    {
        $host = '';

        if ($this->subdomain()) {
            $host .= $this->subdomain;
        }

        if ($this->domain()) {
            $host .= ($host !== '' ? '.' : '') . $this->domain();
        }

        if ($host !== '') {
            $this->host = $host;
        } else {
            $this->host = null;
        }
    }

    /**
     * Replace the host. If it contains a registrable domain, update the domain parts, otherwise set them to null.
     *
     * @param string $newHost
     */
    private function replaceHost(string $newHost = '')
    {
        $this->host = $newHost;
        $newSuffix = $this->parser->getDomainSuffixFromHost($newHost);

        if ($newSuffix) {
            $this->domain = $this->parser->getDomainFromHost($newHost, $newSuffix);
            $this->domainSuffix = $newSuffix;
            $this->subdomain = $this->parser->getSubdomainFromHost($this->host, $this->domain);
        } else {
            $this->domain = null;
            $this->domainSuffix = null;
            $this->subdomain = null;
        }
    }

    /**
     * Set a new domain suffix and replace it in the full registrable domain.
     *
     * @param string $newSuffix
     */
    private function replaceDomainSuffix(string $newSuffix = '')
    {
        // It can be a problem if the subdomain class property isn't set at this point (parsing the host parts is
        // deferred in the init() method), because when the full host will be updated through concatenating the host
        // parts, after replacing the domain suffix here, it will try to extract the subdomain from host minus domain.
        // When the domain is already updated and the host isn't, this will return a broken subdomain. So call the
        // subdomain method here, so the class property gets filled and will just be returned when recreating the host.
        $this->subdomain();

        $currentSuffix = $this->domainSuffix();
        $this->domain = Parser::stripFromEnd($this->domain, $currentSuffix) . $newSuffix;
        $this->domainSuffix = $newSuffix;
    }

    /**
     * Replace the registrable domain (or only the domain label if $withoutSuffix = true).
     * If suffix is included, extract it and update the suffix too.
     *
     * @param string $newDomain
     * @param bool $withoutSuffix
     */
    private function replaceDomain(string $newDomain = '', bool $withoutSuffix = false)
    {
        // This is for the same reason as explained in the comment in the replaceDomainSuffix() method above.
        $this->subdomain();

        if ($withoutSuffix === true) {
            $this->domain = $newDomain . '.' . $this->domainSuffix;
        } else {
            // The following shouldn't return null, because the new domain should have been validated already
            // in the domain() method. The validation should include a check for a domain suffix.
            $this->domainSuffix = $this->parser->getDomainSuffixFromHost($newDomain);
            $this->domain = $newDomain;
        }
    }

    /**
     * @return bool
     */
    private function hasHost() : bool
    {
        if (is_string($this->host) && $this->host !== '') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function hasDomain() : bool
    {
        if (is_string($this->domain()) && $this->domain() !== '') {
            return true;
        }

        return false;
    }
}
