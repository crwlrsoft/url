<?php

namespace Crwlr\Url;

use Crwlr\Url\Exceptions\InvalidUrlException;

/**
 * Class Url
 *
 * This class is the central unit of this package. It represents a url, gives access to its components and also
 * to further functionality like resolving relative urls to absolute ones and comparing single components of different
 * urls.
 *
 * @author otsch
 * @link https://www.crwlr.software/packages/url Documentation
 */

class Url
{
    /**
     * All url components.
     *
     * @var string
     */
    private $url, $scheme, $user, $pass, $host, $domain, $domainSuffix, $subdomain, $port, $path, $query, $fragment;

    /**
     * List of all components including alias method names.
     *
     * Used to verify if a private property can be accessed via magic __get() and __set().
     *
     * @var string[]|array
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
     * @var Validator
     */
    private $validator;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @param string|Url $url
     * @param Validator $validator
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function __construct($url, $validator = null)
    {
        if (!is_string($url) && !$url instanceof Url) {
            throw new \InvalidArgumentException('Param $url must either be of type string or an instance of Url.');
        }

        $this->validator = ($validator instanceof Validator) ? $validator : new Validator(Helpers::punyCode());
        $this->decorate($url);
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
     * Returns a new Url instance with param $url.
     *
     * @param string $url
     * @return Url
     * @throws InvalidUrlException
     */
    public static function parse(string $url = '')
    {
        return new self($url);
    }

    /**
     * Get or set the scheme component.
     *
     * @param null|string $scheme
     * @return string|null|Url
     */
    public function scheme(string $scheme = null)
    {
        if ($scheme === null) {
            return $this->scheme;
        } elseif ($scheme === '') {
            $this->scheme = null;
        } else {
            $this->scheme = $this->validator->scheme($scheme) ?: $this->scheme;
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the user component.
     *
     * When param $user is an empty string, the pass(word) component will also be reset.
     *
     * @param null|string|int $user
     * @return string|null|Url
     */
    public function user($user = null)
    {
        if ($user === null) {
            return $this->user;
        } elseif ($user === '') {
            $this->user = $this->pass = null;
        } else {
            $this->user = $this->validator->userOrPassword($user) ?: $this->user;
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the password component.
     *
     * @param null|string|int $password
     * @return string|null|Url
     */
    public function password($password = null)
    {
        if ($password === null) {
            return $this->pass;
        } elseif ($password === '') {
            $this->pass = null;
        } else {
            $this->pass = $this->validator->userOrPassword($password) ?: $this->pass;
        }

        return $this->updateFullUrlAndReturnInstance();
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
     * Get the url authority (= [userinfo"@"]host[":"port]).
     *
     * @return string
     */
    public function authority() : string
    {
        $authority = '';

        if ($this->host()) {
            if ($this->user()) {
                $authority .= $this->user() . ($this->pass() ? ':' . $this->pass() : '') . '@';
            }

            $authority .= $this->host() . ($this->port() ? ':' . $this->port() : '');
        }

        return $authority;
    }

    /**
     * Get or set the host component.
     *
     * @param null|string $host
     * @return string|null|Url
     */
    public function host($host = null)
    {
        if ($host === null) {
            return $this->host;
        } elseif ($host === '') {
            $this->host = $this->domain = $this->domainSuffix = $this->subdomain = null;
        } else {
            $validHost = $this->validator->host($host);

            if ($validHost) {
                $this->replaceHost($validHost);
            }
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the registrable domain.
     *
     * As all component names are rather short it's just called domain() instead of registrableDomain().
     *
     * @param null|string $domain
     * @return string|null|Url
     */
    public function domain(string $domain = null)
    {
        if ($domain === null) {
            return $this->returnDomain();
        }

        $domain = $this->validator->domain($domain);

        if ($domain) {
            $this->replaceDomain($domain);
            $this->updateHost();
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * Get or set the domain label.
     *
     * That's the registrable domain without the domain suffix (e.g. domain: "crwlr.software" => domain label: "crwlr").
     * It can only be set when the current url contains a host with a registrable domain.
     *
     * @param null|string|int $domainLabel
     * @return string|null|Url
     */
    public function domainLabel($domainLabel = null)
    {
        if ($domainLabel === null) {
            return $this->domain() ? Helpers::getDomainLabelFromDomain($this->domain()) : null;
        }

        if (!empty($this->domain())) {
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
     * Get or set the domain suffix.
     *
     * domain: "crwlr.software" => domain suffix: "software"
     * It can only be set when the current url contains a host with a registrable domain.
     *
     * @param null|string $domainSuffix
     * @return string|null|Url
     */
    public function domainSuffix(string $domainSuffix = null)
    {
        if ($domainSuffix === null) {
            if ($this->domainSuffix === null && !empty($this->host)) {
                $this->domainSuffix = Helpers::getDomainSuffixFromHost($this->host);
            }

            return $this->domainSuffix;
        }

        if (!empty($this->domain())) {
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
     * Get or set the subdomain.
     *
     * host: "www.crwlr.software" => subdomain: "www"
     * It can only be set when the current url contains a host with a registrable domain.
     *
     * @param null|string $subdomain
     * @return string|null|Url
     */
    public function subdomain($subdomain = null)
    {
        if ($subdomain === null) {
            if ($this->subdomain === null && !empty($this->host)) {
                $this->subdomain = Helpers::getSubdomainFromHost($this->host, $this->domain());
            }

            return $this->subdomain;
        }

        if (!empty($this->domain())) {
            $subdomain = $this->validator->subdomain($subdomain);

            if ($subdomain) {
                $this->subdomain = $subdomain;
                $this->updateHost();
                $this->updateFullUrl();
            }
        }

        return $this;
    }

    /**
     * Get or set the port component.
     *
     * @param null|int|string $port
     * @return int|null|Url
     */
    public function port($port = null)
    {
        if ($port === null) {
            $scheme = $this->scheme();

            if ($scheme && $this->port === Helpers::getStandardPortByScheme($scheme)) {
                return null;
            }

            return $this->port;
        }

        $port = $this->validator->port($port);

        if ($port !== false) {
            $this->port = $port;
            $this->updateFullUrl();
        }

        return $this;
    }

    public function resetPort()
    {
        $this->port = null;
        $this->updateFullUrl();
    }

    /**
     * Get or set the path component.
     *
     * @param null|string $path
     * @return string|null|Url
     */
    public function path(string $path = null)
    {
        if ($path === null) {
            return $this->path;
        }

        $path = $this->validator->path($path);

        if ($path || $path === '') {
            $this->path = $path;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * Get or set the query component (as string).
     *
     * @param null|string|array $query
     * @return string|null|Url
     */
    public function query($query = null)
    {
        if ($query === null) {
            return $this->query;
        }

        $query = $this->validator->query($query);

        if ($query) {
            $this->query = $query;
            $this->updateFullUrl();
        } elseif (trim($query) === '') {
            $this->query = null;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * Get or set the query component as array.
     *
     * @param null|array $query
     * @return array|Url
     */
    public function queryArray(array $query = null)
    {
        if ($query === null) {
            if (!$this->query) {
                return [];
            }

            return Helpers::queryStringToArray($this->query);
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
     * Get or set the fragment component.
     *
     * @param null|string $fragment
     * @return string|null|Url
     */
    public function fragment(string $fragment = null)
    {
        if ($fragment === null) {
            return $this->fragment;
        }

        $fragment = $this->validator->fragment($fragment);

        if ($fragment) {
            $this->fragment = $fragment;
            $this->updateFullUrl();
        } elseif (trim($fragment) === '') {
            $this->fragment = null;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * Returns scheme + authority.
     *
     * https://www.example.com/path?query=string => https://www.example.com
     *
     * @return string
     */
    public function root() : string
    {
        return (!empty($this->scheme) ? $this->scheme . ':' : '') .
            ($this->authority() === '' ? '' : '//' . $this->authority());
    }

    /**
     * Returns path + query + fragment.
     *
     * https://www.example.com/path?query=string#fragment => /path?query=string#fragment
     *
     * @return string
     */
    public function relative() : string
    {
        return ($this->path() ?: '') .
            ($this->query() ? '?' . $this->query() : '') .
            ($this->fragment() ? '#' . $this->fragment() : '');
    }

    /**
     * Resolve a relative (or absolute) url against the url of the current instance.
     *
     * That basically means you get an absolute url from any href link attribute found on a web page.
     * When the provided input already is an absolute url, it's just returned as it is (except for validation changes
     * like percent encoding).
     *
     * @param string $relativeUrl
     * @return Url
     * @throws InvalidUrlException
     */
    public function resolve(string $relativeUrl = '') : Url
    {
        return $this->resolver()->resolve($relativeUrl, $this);
    }

    /**
     * Compare component X (e.g. host) of the current instance url and the url from input parameter $compareWithUrl.
     *
     * @param Url|string $compareWithUrl
     * @param string $componentName
     * @return bool
     */
    public function compare($compareWithUrl, string $componentName) : bool
    {
        if (is_string($compareWithUrl)) {
            try {
                $compareWithUrl = new Url($compareWithUrl);
            } catch (\Exception $exception) {
                return false;
            }
        } elseif (!$compareWithUrl instanceof Url) {
            return false;
        }

        if (in_array($componentName, $this->components)) {
            return ($this->{$componentName}() === $compareWithUrl->{$componentName}());
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
     * Validate the input url and decorate the current instance with components.
     *
     * The input url must either be a string or an instance of this class. Decoration from another instance is just
     * like cloning and it's necessary for the PSR-7 UriInterface Adapter class.
     *
     * In case the input url is a string the validate() method below returns an array with valid components (or
     * throws an InvalidUrlException).
     *
     * @param $url
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    private function decorate($url)
    {
        $url = $this->validate($url);
        $this->url = $url instanceof Url ? $url->toString() : $url['url'];

        foreach ($this->components as $componentName) {
            if (property_exists($this, $componentName)) {
                if ($url instanceof Url) {
                    $this->{$componentName} = $url->{$componentName};
                } elseif (isset($url[$componentName])) {
                    $this->{$componentName} = $url[$componentName];
                }
            }
        }
    }

    /**
     * Parse and validate $url in case it's a string, return when it's an instance of Url or throw an Exception.
     *
     * @param $url
     * @return array|Url
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    private function validate($url)
    {
        if (is_string($url)) {
            $validComponents = $this->validator->url($url);

            if (!is_array($validComponents)) {
                throw new InvalidUrlException($url . ' is not a valid url.');
            }
        } elseif (!$url instanceof Url) {
            throw new \InvalidArgumentException('Provided url must either be a string or an Url object.');
        }

        return isset($validComponents) ? $validComponents : $url;
    }

    /**
     * Regenerate the full host string after changing a part of the host (subdomain, domain, suffix).
     */
    private function updateHost()
    {
        $host = ($this->subdomain() ? $this->subdomain() . '.' : '') . ($this->domain() ?: '');

        if ($host !== '') {
            $this->host = $host;
        } else {
            $this->host = null;
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
     * Replace the host.
     *
     * If it contains a registrable domain, update the domain parts, otherwise set them to null.
     *
     * @param string $newHost
     */
    private function replaceHost(string $newHost = '')
    {
        $this->host = $newHost;
        $newSuffix = Helpers::getDomainSuffixFromHost($newHost);

        if ($newSuffix) {
            $this->domain = Helpers::getDomainFromHost($newHost, $newSuffix);
            $this->domainSuffix = $newSuffix;
            $this->subdomain = Helpers::getSubdomainFromHost($this->host, $this->domain);
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
        // deferred in the decorate() method), because when the full host will be updated through concatenating the host
        // parts, after replacing the domain suffix here, it will try to extract the subdomain from host minus domain.
        // When the domain is already updated and the host isn't, this will return a broken subdomain. So call the
        // subdomain method here, so the class property gets filled and will just be returned when recreating the host.
        $this->subdomain();

        $currentSuffix = $this->domainSuffix();
        $this->domain = Helpers::stripFromEnd($this->domain, $currentSuffix) . $newSuffix;
        $this->domainSuffix = $newSuffix;
    }

    /**
     * Replace the registrable domain.
     *
     * Or only the domain label if $withoutSuffix = true. If suffix is included, extract it and update the suffix too.
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
            $this->domainSuffix = Helpers::getDomainSuffixFromHost($newDomain);
            $this->domain = $newDomain;
        }
    }

    /**
     * Return the registrable domain within the host component of the current url (when it has both).
     *
     * @return null|string
     */
    private function returnDomain()
    {
        if ($this->domain === null && !empty($this->host)) {
            $this->domain = Helpers::getDomainFromHost($this->host, $this->domainSuffix());
        }

        return $this->domain;
    }

    /**
     * @return Url
     */
    private function updateFullUrlAndReturnInstance() : Url
    {
        $this->updateFullUrl();
        return $this;
    }

    /**
     * @return Resolver
     */
    private function resolver() : Resolver
    {
        if (!$this->resolver) {
            $this->resolver = new Resolver($this->validator);
        }

        return $this->resolver;
    }
}
