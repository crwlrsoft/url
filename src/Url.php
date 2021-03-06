<?php

namespace Crwlr\Url;

use Crwlr\Url\Exceptions\InvalidUrlComponentException;
use Crwlr\Url\Exceptions\InvalidUrlException;
use InvalidArgumentException;

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
        'authority',
        'user',
        'pass',
        'password',
        'userInfo',
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
     * @throws InvalidArgumentException
     */
    public function __construct($url)
    {
        $url = $this->validate($url);
        $this->populate($url);
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
     * @throws InvalidUrlComponentException
     */
    public function scheme(?string $scheme = null)
    {
        if ($scheme === null) {
            return $this->scheme;
        } elseif ($scheme === '') {
            $this->scheme = null;
        } else {
            $this->scheme = $this->validateStringComponent('scheme', $scheme);
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the url authority (= [userinfo"@"]host[":"port]).
     *
     * @param null|string
     * @return string|null|Url
     * @throws InvalidUrlComponentException
     */
    public function authority(?string $authority = null)
    {
        if ($authority === null && $this->host()) {
            return Helpers::buildAuthorityFromComponents($this->authorityComponents());
        } elseif ($authority === null) {
            return null;
        } elseif ($authority === '') {
            $this->host = $this->user = $this->pass = $this->port = null;
        } else {
            $this->validatePathStartsWithSlash();
            $validAuthorityComponents = Validator::authorityComponents($authority);

            if ($validAuthorityComponents === null) {
                throw new InvalidUrlComponentException('Invalid authority.');
            }

            $this->host = new Host($validAuthorityComponents['host']);
            $this->user = $validAuthorityComponents['user'];
            $this->pass = $validAuthorityComponents['password'];
            $this->port = $validAuthorityComponents['port'];
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
     * @throws InvalidUrlComponentException
     */
    public function user(?string $user = null)
    {
        if ($user === null) {
            return $this->user;
        } elseif ($user === '') {
            $this->user = $this->pass = null;
        } else {
            $this->user = $this->validateStringComponent('user', $user);
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the password component.
     *
     * @param null|string $password
     * @return string|null|Url
     * @throws InvalidUrlComponentException
     */
    public function password(?string $password = null)
    {
        if ($password === null) {
            return $this->pass;
        } elseif ($password === '') {
            $this->pass = null;
        } else {
            $this->pass = $this->validateStringComponent('password', $password);
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Alias for method password().
     *
     * @param null|string $pass
     * @return string|null|Url
     * @throws InvalidUrlComponentException
     */
    public function pass(?string $pass = null)
    {
        return $this->password($pass);
    }

    /**
     * Get or set user information (user, password as one string) user[:password]
     *
     * @param string|null $userInfo
     * @return string|null|Url
     * @throws InvalidUrlComponentException
     */
    public function userInfo(?string $userInfo = null)
    {
        if ($userInfo === null) {
            return $this->user ? Helpers::buildUserInfoFromComponents($this->userInfoComponents()) : null;
        } elseif ($userInfo === '') {
            $this->user = $this->pass = null;
        } else {
            $validUserInfoComponents = Validator::userInfoComponents($userInfo);

            if ($validUserInfoComponents === null) {
                throw new InvalidUrlComponentException('Invalid userInfo.');
            }

            $this->user = $validUserInfoComponents['user'];
            $this->pass = $validUserInfoComponents['password'];
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the host component.
     *
     * @param null|string $host
     * @return string|null|Url
     * @throws InvalidUrlComponentException
     */
    public function host(?string $host = null)
    {
        if ($host === null) {
            return $this->host instanceof Host ? $this->host->__toString() : null;
        } elseif ($host === '') {
            $this->host = null;
        } else {
            $this->validatePathStartsWithSlash();
            $validHost = $this->validateStringComponent('host', $host);
            $this->host = new Host($validHost);
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the registrable domain.
     *
     * As all component names are rather short it's just called domain() instead of registrableDomain().
     * When the current instance has no host component, the domain will also be the full new host.
     *
     * @param null|string $domain
     * @return string|null|Url
     * @throws InvalidUrlComponentException
     */
    public function domain(?string $domain = null)
    {
        if ($domain === null) {
            return $this->host instanceof Host ? $this->host->domain() : null;
        }

        $validDomain = $this->validateStringComponent('domain', $domain);

        if ($this->host instanceof Host) {
            $this->host->domain($validDomain);
        } else {
            $this->host = new Host($validDomain);
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
     * @throws InvalidUrlComponentException
     */
    public function domainLabel(?string $domainLabel = null)
    {
        if ($domainLabel === null) {
            return $this->host instanceof Host ? $this->host->domainLabel() : null;
        }

        if (!$this->host instanceof Host || empty($this->host->domain())) {
            throw new InvalidUrlComponentException(
                'Domain label can\'t be set because the current host doesn\'t contain a registered domain.'
            );
        }

        $this->host->domainLabel(
            $this->validateStringComponent('domainLabel', $domainLabel)
        );

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
     * @throws InvalidUrlComponentException
     */
    public function domainSuffix(?string $domainSuffix = null)
    {
        if ($domainSuffix === null) {
            return $this->host instanceof Host ? $this->host->domainSuffix() : null;
        }

        if (!$this->host instanceof Host || empty($this->host->domain())) {
            throw new InvalidUrlComponentException(
                'Domain suffix can\'t be set because the current host doesn\'t contain a registered domain.'
            );
        }

        $this->host->domainSuffix(
            $this->validateStringComponent('domainSuffix', $domainSuffix)
        );

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
     * @throws InvalidUrlComponentException
     */
    public function subdomain(?string $subdomain = null)
    {
        if ($subdomain === null) {
            return $this->host instanceof Host ? $this->host->subdomain() : null;
        }

        if (!$this->host instanceof Host || empty($this->host->domain())) {
            throw new InvalidUrlComponentException(
                'Subdomain can\'t be set because the current host doesn\'t contain a registered domain.'
            );
        }

        $this->host->subdomain(
            $this->validateStringComponent('subdomain', $subdomain)
        );

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the port component.
     *
     * Returns the set port component only when it's not the standard port of the current scheme.
     *
     * @param null|int $port
     * @return int|null|Url
     * @throws InvalidUrlComponentException
     */
    public function port(?int $port = null)
    {
        if ($port === null) {
            $scheme = $this->scheme();

            return ($scheme && $this->port === Helpers::getStandardPortByScheme($scheme)) ? null : $this->port;
        }

        $this->port = $this->validateIntComponent('port', $port);

        return $this->updateFullUrlAndReturnInstance();
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

        $this->path = $this->validateStringComponent('path', $path);

        return $this->updateFullUrlAndReturnInstance();
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
        } elseif ($query === '') {
            $this->query = null;
        } else {
            $this->query = $this->validateStringComponent('query', $query);
        }

        return $this->updateFullUrlAndReturnInstance();
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
            return $this->query ? Helpers::queryStringToArray($this->query) : [];
        } elseif (is_array($query)) {
            $this->query = $this->validateStringComponent('query', http_build_query($query));
        }

        return $this->updateFullUrlAndReturnInstance();
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
        } elseif ($fragment === '') {
            $this->fragment = null;
        } else {
            $this->fragment = $this->validateStringComponent('fragment', $fragment);
        }

        return $this->updateFullUrlAndReturnInstance();
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
        $authority = $this->authority();

        return (!empty($this->scheme) ? $this->scheme . ':' : '') . ($authority ? '//' . $authority : '');
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

        if ($path && !$this->authority() && substr($path, 0, 2) === '//') {
            $path = preg_replace('/^\/{2,}/', '/', $path);
        }

        return ($path ?: '') .
            ($this->query() ? '?' . $this->query() : '') .
            ($this->fragment() ? '#' . $this->fragment() : '');
    }

    /**
     * Is the current url a relative reference
     *
     * Returns true if the current url does not begin with a scheme.
     * https://tools.ietf.org/html/rfc3986#section-4.1
     *
     * @return bool
     */
    public function isRelativeReference(): bool
    {
        if ($this->scheme() === null) {
            return true;
        }

        return false;
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     */
    public function isSchemeEqualIn($url): bool
    {
        return $this->compare($url, 'scheme');
    }

    /**
     * Returns true when the authority is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidArgumentException
     */
    public function isAuthorityEqualIn($url): bool
    {
        return $this->compare($url, 'authority');
    }

    /**
     * Returns true when the user is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     */
    public function isPasswordEqualIn($url): bool
    {
        return $this->compare($url, 'password');
    }

    /**
     * Returns true when the user information (both user and password) is the same in the current instance and the
     * url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidArgumentException
     */
    public function isUserInfoEqualIn($url): bool
    {
        return $this->compare($url, 'userInfo');
    }

    /**
     * Returns true when the host component is the same in the current instance and the url you want to compare.
     *
     * @param $url
     * @return bool
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * Populate the url components from an array or another instance of this class
     *
     * This method does no validation so the components coming in via an array need to be valid!
     * Population from another instance is just like cloning and it's necessary for the PSR-7 UriInterface Adapter
     * class.
     *
     * @param string[]|Url $components
     */
    private function populate($components): void
    {
        $this->url = $components instanceof Url ? $components->toString() : $components['url'];

        foreach ($this->components as $componentName) {
            if (property_exists($this, $componentName)) {
                if ($components instanceof Url) {
                    $this->{$componentName} = $components->{$componentName};
                } elseif (isset($components[$componentName])) {
                    if ($componentName === 'host') {
                        $this->{$componentName} = new Host($components[$componentName]);
                    } else {
                        $this->{$componentName} = $components[$componentName];
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
     * @throws InvalidArgumentException
     * @throws InvalidUrlException
     */
    private function validate($url)
    {
        if (!is_string($url) && !$url instanceof Url) {
            throw new InvalidArgumentException('Param $url must either be of type string or an instance of Url.');
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
        if (in_array($componentName, $this->components, true)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $componentName
     * @param string $componentValue
     * @return string
     * @throws InvalidUrlComponentException
     */
    private function validateStringComponent(string $componentName, string $componentValue): string
    {
        return $this->validateComponentValue($componentName, $componentValue);
    }

    /**
     * @param string $componentName
     * @param int $componentValue
     * @return int
     * @throws InvalidUrlComponentException
     */
    private function validateIntComponent(string $componentName, int $componentValue): int
    {
        return $this->validateComponentValue($componentName, $componentValue);
    }

    /**
     * @param string $componentName
     * @param $componentValue
     * @return int|string
     * @throws InvalidUrlComponentException
     */
    private function validateComponentValue(string $componentName, $componentValue)
    {
        $validComponentValue = Validator::callValidationByComponentName($componentName, $componentValue);

        if ($validComponentValue === null) {
            throw new InvalidUrlComponentException('Invalid ' . $componentName . '.');
        }

        return $validComponentValue;
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
     * Throws an Exception when the current path doesn't start with slash
     *
     * Used in authority and host methods, because it's not allowed to set an authority when path doesn't start with
     * slash.
     *
     * @throws InvalidUrlComponentException
     */
    private function validatePathStartsWithSlash()
    {
        if ($this->path() && $this->path() !== '' && !Helpers::startsWith($this->path(), '/', 1)) {
            throw new InvalidUrlComponentException(
                'The current path doesn\'t start with a slash which is why an authority component can\'t be ' .
                'added to the url.'
            );
        }
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
     * @throws InvalidArgumentException
     */
    private function compare($compareToUrl, ?string $componentName = null): bool
    {
        if (is_string($compareToUrl)) {
            try {
                $compareToUrl = new Url($compareToUrl);
            } catch (InvalidUrlException $exception) {
                // When the url to compare is invalid (and thereby has no valid components) it (or any component)
                // can't be equal to this url instance, so return false.
                return false;
            }
        } elseif (!$compareToUrl instanceof Url) {
            throw new InvalidArgumentException('Param must be either string or instance of Url.');
        }

        if ($componentName === null) {
            return $this->toString() === $compareToUrl->toString();
        } elseif ($this->isValidComponentName($componentName)) {
            return $this->{$componentName}() === $compareToUrl->{$componentName}();
        }

        return false;
    }

    /**
     * @return array
     */
    private function authorityComponents(): array
    {
        return ['host' => $this->host(), 'user' => $this->user, 'password' => $this->pass, 'port' => $this->port()];
    }

    /**
     * @return array
     */
    private function userInfoComponents(): array
    {
        return ['user' => $this->user, 'password' => $this->pass];
    }
}
