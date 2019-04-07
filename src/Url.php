<?php

namespace Crwlr\Url;

use Crwlr\Url\Exceptions\InvalidUrlException;

/**
 * Class Url
 *
 * This class is the central unit of this package. It represents a url, gives access to its components and also
 * to further functionality like resolving relative urls to absolute ones and comparing (components of) another url to
 * the current instance.
 *
 * @link https://www.crwlr.software/packages/url Documentation
 */

class Url
{
    /**
     * All (string) url components.
     *
     * @var string|null
     */
    private $url, $scheme, $user, $pass, $host, $path, $query, $fragment;

    /**
     * Port url component (int).
     *
     * @var int|null
     */
    private $port;

    /**
     * List of all components including alias method names.
     *
     * Used to verify if a private property (or host component) can be accessed via magic __get() and __set().
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
     * @var Resolver
     */
    private $resolver;

    /**
     * @param string|Url $url
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function __construct($url)
    {
        $url = $this->validate($url);
        $this->decorate($url);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($this->isValidComponentName($name)) {
            return $this->$name();
        }

        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function __set(string $name, $value)
    {
        if ($this->isValidComponentName($name)) {
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
    public static function parse(string $url = ''): Url
    {
        return new Url($url);
    }

    /**
     * Get or set the scheme component.
     *
     * @param null|string $scheme
     * @return string|null|Url
     */
    public function scheme(?string $scheme = null)
    {
        if ($scheme === null) {
            return $this->scheme;
        } elseif ($scheme === '') {
            $this->scheme = null;
        } else {
            $this->scheme = Validator::scheme($scheme) ?: $this->scheme;
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the user component.
     *
     * When param $user is an empty string, the pass(word) component will also be reset.
     *
     * @param null|string $user
     * @return string|null|Url
     */
    public function user(?string $user = null)
    {
        if ($user === null) {
            return $this->user;
        } elseif ($user === '') {
            $this->user = $this->pass = null;
        } else {
            $this->user = Validator::user($user) ?: $this->user;
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the password component.
     *
     * @param null|string $password
     * @return string|null|Url
     */
    public function password(?string $password = null)
    {
        if ($password === null) {
            return $this->pass;
        } elseif ($password === '') {
            $this->pass = null;
        } else {
            $this->pass = Validator::password($password) ?: $this->pass;
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Alias for method password().
     *
     * @param null|string $pass
     * @return string|null|Url
     */
    public function pass(?string $pass = null)
    {
        return $this->password($pass);
    }

    /**
     * Get the url authority (= [userinfo"@"]host[":"port]).
     *
     * @return string
     */
    public function authority(): string
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
    public function host(?string $host = null)
    {
        if ($host === null) {
            return $this->host instanceof Host ? $this->host->__toString() : null;
        } elseif ($host === '') {
            $this->host = null;
            return $this->updateFullUrlAndReturnInstance();
        }

        $validHost = Validator::host($host);

        if ($validHost) {
            $this->host = new Host($validHost);
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
    public function domain(?string $domain = null)
    {
        if ($domain === null) {
            return $this->host instanceof Host ? $this->host->domain() : null;
        }

        $domain = Validator::domain($domain);

        if ($domain) {
            if ($this->host instanceof Host) {
                $this->host->domain($domain);
            } else {
                $this->host = new Host($domain);
            }
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the domain label.
     *
     * That's the registrable domain without the domain suffix (e.g. domain: "crwlr.software" => domain label: "crwlr").
     * It can only be set when the current url contains a host with a registrable domain.
     *
     * @param null|string $domainLabel
     * @return string|null|Url
     */
    public function domainLabel(?string $domainLabel = null)
    {
        if ($domainLabel === null) {
            return $this->host instanceof Host ? $this->host->domainLabel() : null;
        }

        if ($this->host instanceof Host && !empty($this->host->domain())) {
            $domainLabel = Validator::domainLabel($domainLabel);

            if ($domainLabel) {
                $this->host->domainLabel($domainLabel);
            }
        }

        return $this->updateFullUrlAndReturnInstance();
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
    public function domainSuffix(?string $domainSuffix = null)
    {
        if ($domainSuffix === null) {
            return $this->host instanceof Host ? $this->host->domainSuffix() : null;
        } elseif ($this->host instanceof Host && !empty($this->host->domain())) {
            $domainSuffix = Validator::domainSuffix($domainSuffix);

            if ($domainSuffix) {
                $this->host->domainSuffix($domainSuffix);
            }
        }

        return $this->updateFullUrlAndReturnInstance();
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
    public function subdomain(?string $subdomain = null)
    {
        if ($subdomain === null) {
            return $this->host instanceof Host ? $this->host->subdomain() : null;
        } elseif ($this->host instanceof Host && !empty($this->host->domain())) {
            $subdomain = Validator::subdomain($subdomain);

            if ($subdomain) {
                $this->host->subdomain($subdomain);
            }
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the port component.
     *
     * @param null|int $port
     * @return int|null|Url
     */
    public function port(?int $port = null)
    {
        if ($port === null) {
            $scheme = $this->scheme();

            if ($scheme && $this->port === Helpers::getStandardPortByScheme($scheme)) {
                return null;
            }

            return $this->port;
        }

        $port = Validator::port($port);

        if ($port !== null) {
            $this->port = $port;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * Reset the port component to null.
     */
    public function resetPort(): void
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
    public function path(?string $path = null)
    {
        if ($path === null) {
            return $this->path;
        }

        $path = Validator::path($path, !empty($this->authority()));

        if ($path || $path === '') {
            $this->path = $path;
            $this->updateFullUrl();
        }

        return $this;
    }

    /**
     * Get or set the query component (as string).
     *
     * @param null|string $query
     * @return string|null|Url
     */
    public function query(?string $query = null)
    {
        if ($query === null) {
            return $this->query;
        }

        $query = Validator::query($query);

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
    public function queryArray(?array $query = null)
    {
        if ($query === null) {
            if (!$this->query) {
                return [];
            }

            return Helpers::queryStringToArray($this->query);
        } elseif (is_array($query)) {
            $query = Validator::query(http_build_query($query));

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
    public function fragment(?string $fragment = null)
    {
        if ($fragment === null) {
            return $this->fragment;
        }

        $fragment = Validator::fragment($fragment);

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
    public function root(): string
    {
        return (!empty($this->scheme) ? $this->scheme . ':' : '') .
            ($this->authority() === '' ? '' : '//' . $this->authority());
    }

    /**
     * Returns path + query + fragment.
     *
     * https://www.example.com/path?query=string#fragment => /path?query=string#fragment
     *
     * If the current instance has no authority, the path can not start with more than one slash.
     * If that's the case, starting slashes in the path are reduced to one in the return value of this method.
     *
     * @return string
     */
    public function relative(): string
    {
        $path = $this->path();

        if ($path && $this->authority() === '' && substr($path, 0, 2) === '//') {
            $path = preg_replace('/^\/{2,}/', '/', $path);
        }

        return ($path ?: '') .
            ($this->query() ? '?' . $this->query() : '') .
            ($this->fragment() ? '#' . $this->fragment() : '');
    }

    /**
     * Resolve a relative reference against the url of the current instance.
     *
     * That basically means you get an absolute url from any relative reference (link href, image src, etc.) found on
     * a web page.
     * When the provided input already is an absolute url, it's just returned as it is (except for validation changes
     * like percent encoding).
     *
     * @param string $relativeUrl
     * @return Url
     * @throws InvalidUrlException
     */
    public function resolve(string $relativeUrl = ''): Url
    {
        return $this->resolver()->resolve($relativeUrl, $this);
    }

    /**
     * Return true when the current url contains an internationalized domain name in the host component.
     *
     * @return bool
     */
    public function hasIdn(): bool
    {
        return $this->host instanceof Host ? $this->host->hasIdn() : false;
    }

    /**
     * Returns true if the current instance url is equal to the url you want to compare.
     *
     * @param Url|string $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isEqualTo($url): bool
    {
        return $this->compare($url);
    }

    /**
     * Returns true when some component is the same in the current instance and the url you want to compare.
     *
     * @param Url|string $url
     * @param string $componentName
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isComponentEqualIn($url, string $componentName): bool
    {
        return $this->compare($url, $componentName);
    }

    /**
     * Returns true when the scheme component is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isSchemeEqualIn($url): bool
    {
        return $this->compare($url, 'scheme');
    }

    /**
     * Returns true when the user is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isUserEqualIn($url): bool
    {
        return $this->compare($url, 'user');
    }

    /**
     * Returns true when the password is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isPasswordEqualIn($url): bool
    {
        return $this->compare($url, 'password');
    }

    /**
     * Returns true when the host component is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isHostEqualIn($url): bool
    {
        return $this->compare($url, 'host');
    }

    /**
     * Returns true when the registrable domain is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isDomainEqualIn($url): bool
    {
        return $this->compare($url, 'domain');
    }

    /**
     * Returns true when the domain label is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isDomainLabelEqualIn($url): bool
    {
        return $this->compare($url, 'domainLabel');
    }

    /**
     * Returns true when the domain suffix is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isDomainSuffixEqualIn($url): bool
    {
        return $this->compare($url, 'domainSuffix');
    }

    /**
     * Returns true when the subdomain is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isSubdomainEqualIn($url): bool
    {
        return $this->compare($url, 'subdomain');
    }

    /**
     * Returns true when the port component is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isPortEqualIn($url): bool
    {
        return $this->compare($url, 'port');
    }

    /**
     * Returns true when the path component is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isPathEqualIn($url): bool
    {
        return $this->compare($url, 'path');
    }

    /**
     * Returns true when the query component is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isQueryEqualIn($url): bool
    {
        return $this->compare($url, 'query');
    }

    /**
     * Returns true when the fragment component is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    public function isFragmentEqualIn($url): bool
    {
        return $this->compare($url, 'fragment');
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString(): string
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
     * @param string|Url $url
     */
    private function decorate($url): void
    {
        $this->url = $url instanceof Url ? $url->toString() : $url['url'];

        foreach ($this->components as $componentName) {
            if (property_exists($this, $componentName)) {
                if ($url instanceof Url) {
                    $this->{$componentName} = $url->{$componentName};
                } elseif (isset($url[$componentName])) {
                    if ($componentName === 'host') {
                        $this->{$componentName} = new Host($url[$componentName]);
                    } else {
                        $this->{$componentName} = $url[$componentName];
                    }
                }
            }
        }
    }

    /**
     * Parse and validate $url in case it's a string, return when it's an instance of Url or throw an Exception.
     *
     * @param string|Url $url
     * @return array|Url
     * @throws \InvalidArgumentException
     * @throws InvalidUrlException
     */
    private function validate($url)
    {
        if (!is_string($url) && !$url instanceof Url) {
            throw new \InvalidArgumentException('Param $url must either be of type string or an instance of Url.');
        }

        if ($url instanceof Url) {
            return $url;
        }

        $validComponents = Validator::urlAndComponents($url);

        if (!is_array($validComponents)) {
            throw new InvalidUrlException($url . ' is not a valid url.');
        }

        return $validComponents;
    }

    /**
     * @param string $componentName
     * @return bool
     */
    private function isValidComponentName(string $componentName): bool
    {
        if (in_array($componentName, $this->components)) {
            return true;
        }

        return false;
    }

    /**
     * Regenerate the full url after changing components.
     */
    private function updateFullUrl(): void
    {
        $this->url = $this->root() . $this->relative();
    }

    /**
     * @return Url
     */
    private function updateFullUrlAndReturnInstance(): Url
    {
        $this->updateFullUrl();

        return $this;
    }

    /**
     * @return Resolver
     */
    private function resolver(): Resolver
    {
        if (!$this->resolver) {
            $this->resolver = new Resolver();
        }

        return $this->resolver;
    }

    /**
     * Compares the current instance with another url.
     *
     * @param $compareToUrl
     * @param string|null $componentName  Compare either only a certain component of the urls or the whole urls if null.
     * @return bool
     * @throws InvalidUrlException
     * @throws \InvalidArgumentException
     */
    private function compare($compareToUrl, ?string $componentName = null): bool
    {
        if (is_string($compareToUrl)) {
            $compareToUrl = new Url($compareToUrl);
        } elseif (!$compareToUrl instanceof Url) {
            throw new \InvalidArgumentException('Param must be either string or instance of Url.');
        }

        if ($componentName === null) {
            return $this->toString() === $compareToUrl->toString();
        } elseif ($this->isValidComponentName($componentName)) {
            return $this->{$componentName}() === $compareToUrl->{$componentName}();
        }

        return false;
    }
}
