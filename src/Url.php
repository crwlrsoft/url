<?php

namespace Crwlr\Url;

use Crwlr\QueryString\Query;
use Crwlr\Url\Exceptions\InvalidUrlComponentException;
use Crwlr\Url\Exceptions\InvalidUrlException;
use Crwlr\Url\Psr\Uri;
use Exception;
use InvalidArgumentException;

/**
 * Class Url
 *
 * This class is the central unit of this package. It represents a URL, gives access to its components and also
 * to further functionality like resolving relative URLs to absolute ones and comparing (components of) another URL to
 * the current instance.
 *
 * @link https://www.crwlr.software/packages/url Documentation
 */

class Url
{
    private ?string $url = null;

    private ?string $scheme = null;

    private ?string $user = null;

    private ?string $pass = null;

    private ?Host $host = null;

    private ?int $port = null;

    private ?string $path = null;

    private string|Query|null $query = null;

    private ?string $fragment = null;

    /**
     * List of all components including alias method names.
     *
     * Used to verify if a private property (or host component) can be accessed via magic __get() and __set().
     *
     * @var string[]
     */
    private array $components = [
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
        'queryString',
        'fragment',
        'root',
        'relative',
    ];

    private ?Resolver $resolver = null;

    /**
     * @throws InvalidUrlException
     * @throws InvalidArgumentException
     */
    public function __construct(string|Url $url)
    {
        if (!$url instanceof Url) {
            $url = $this->validate($url);
        }

        $this->populate($url);
    }

    /**
     * Returns a new `Url` instance with param $url.
     *
     * @throws InvalidUrlException
     */
    public static function parse(string $url = ''): Url
    {
        return new Url($url);
    }

    /**
     * Parses $url to a new instance of the PSR-7 UriInterface compatible `Uri` class.
     *
     * @throws InvalidUrlException
     */
    public static function parsePsr7(string $url = ''): Uri
    {
        return new Uri(Url::parse($url));
    }

    /**
     * Get or set the scheme component.
     *
     * @throws InvalidUrlComponentException|Exception
     */
    public function scheme(?string $scheme = null): string|null|Url
    {
        if ($scheme === null) {
            return $this->scheme;
        } elseif ($scheme === '') {
            $this->scheme = null;
        } else {
            $this->scheme = $this->validateComponentValue('scheme', $scheme);
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the URL authority (= [userinfo"@"]host[":"port]).
     *
     * @throws InvalidUrlComponentException|Exception
     */
    public function authority(?string $authority = null): string|null|Url
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
     * @throws InvalidUrlComponentException|Exception
     */
    public function user(?string $user = null): string|null|Url
    {
        if ($user === null) {
            return $this->user;
        } elseif ($user === '') {
            $this->user = $this->pass = null;
        } else {
            $this->user = $this->validateComponentValue('user', $user);
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the password component.
     *
     * @throws InvalidUrlComponentException|Exception
     */
    public function password(?string $password = null): string|null|Url
    {
        if ($password === null) {
            return $this->pass;
        } elseif ($password === '') {
            $this->pass = null;
        } else {
            $this->pass = $this->validateComponentValue('password', $password);
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Alias for method password().
     *
     * @throws InvalidUrlComponentException|Exception
     */
    public function pass(?string $pass = null): string|null|Url
    {
        return $this->password($pass);
    }

    /**
     * Get or set user information (user, password as one string) user[:password]
     *
     * @throws InvalidUrlComponentException|Exception
     */
    public function userInfo(?string $userInfo = null): string|null|Url
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
     * @throws InvalidUrlComponentException|Exception
     */
    public function host(?string $host = null): string|null|Url
    {
        if ($host === null) {
            return $this->host instanceof Host ? $this->host->__toString() : null;
        } elseif ($host === '') {
            $this->host = null;
        } else {
            $this->validatePathStartsWithSlash();
            $validHost = $this->validateComponentValue('host', $host);
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
     * @throws InvalidUrlComponentException|Exception
     */
    public function domain(?string $domain = null): string|null|Url
    {
        if ($domain === null) {
            return $this->host instanceof Host ? $this->host->domain() : null;
        }

        $validDomain = $this->validateComponentValue('domain', $domain);

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
     * It can only be set when the current URL contains a host with a registrable domain.
     *
     * @throws InvalidUrlComponentException|Exception
     */
    public function domainLabel(?string $domainLabel = null): string|null|Url
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
            $this->validateComponentValue('domainLabel', $domainLabel)
        );

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the domain suffix.
     *
     * domain: "crwlr.software" => domain suffix: "software"
     * It can only be set when the current URL contains a host with a registrable domain.
     *
     * @throws InvalidUrlComponentException|Exception
     */
    public function domainSuffix(?string $domainSuffix = null): string|null|Url
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
            $this->validateComponentValue('domainSuffix', $domainSuffix)
        );

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the subdomain.
     *
     * host: "www.crwlr.software" => subdomain: "www"
     * It can only be set when the current URL contains a host with a registrable domain.
     *
     * @throws InvalidUrlComponentException|Exception
     */
    public function subdomain(?string $subdomain = null): string|null|Url
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
            $this->validateComponentValue('subdomain', $subdomain)
        );

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the port component.
     *
     * Returns the set port component only when it's not the standard port of the current scheme.
     *
     * @throws InvalidUrlComponentException|Exception
     */
    public function port(?int $port = null): int|null|Url
    {
        if ($port === null) {
            $scheme = $this->scheme();

            return ($scheme && $this->port === Helpers::getStandardPortByScheme($scheme)) ? null : $this->port;
        }

        $this->port = $this->validateComponentValue('port', $port);

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Reset the port component to null.
     *
     * @throws Exception
     */
    public function resetPort(): void
    {
        $this->port = null;
        $this->updateFullUrl();
    }

    /**
     * Get or set the path component.
     *
     * @throws Exception
     */
    public function path(?string $path = null): string|null|Url
    {
        if ($path === null) {
            return $this->path;
        }

        $this->path = $this->validateComponentValue('path', $path);

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the query component (as string).
     *
     * @throws Exception
     */
    public function query(?string $query = null): string|null|Url
    {
        if ($query === null) {
            return $this->query instanceof Query ? $this->query->toString() : $this->query;
        } elseif ($query === '') {
            $this->query = null;
        } else {
            $this->query = $this->validateComponentValue('query', $query);
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Get or set the query component as array.
     *
     * @param null|mixed[] $query
     * @return mixed[]|Url
     * @throws Exception
     */
    public function queryArray(?array $query = null): array|Url
    {
        if ($query === null) {
            if ($this->query instanceof Query) {
                return $this->query->toArray();
            } elseif ($this->query) {
                $this->query = Query::fromString($this->query);

                return $this->query->toArray();
            }

            return [];
        } else {
            $this->query = $this->validateComponentValue('query', http_build_query($query));
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * @throws Exception
     */
    public function queryString(): Query
    {
        if (!$this->query instanceof Query) {
            $this->query = Query::fromString($this->query ?? '');

            $url = $this;

            $this->query->setDirtyHook(function () use ($url) {
                $url->updateFullUrl();
            });
        }

        return $this->query;
    }

    /**
     * Get or set the fragment component.
     *
     * @throws Exception
     */
    public function fragment(?string $fragment = null): string|null|Url
    {
        if ($fragment === null) {
            return $this->fragment;
        } elseif ($fragment === '') {
            $this->fragment = null;
        } else {
            $this->fragment = $this->validateComponentValue('fragment', $fragment);
        }

        return $this->updateFullUrlAndReturnInstance();
    }

    /**
     * Returns scheme + authority.
     *
     * https://www.example.com/path?query=string => https://www.example.com
     *
     * @return string
     * @throws Exception
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
     * @throws Exception
     */
    public function relative(): string
    {
        $path = $this->path();

        if ($path && !$this->authority() && str_starts_with($path, '//')) {
            $path = preg_replace('/^\/{2,}/', '/', $path);
        }

        return ($path ?: '') .
            ($this->query() ? '?' . $this->query() : '') .
            ($this->fragment() ? '#' . $this->fragment() : '');
    }

    /**
     * Is the current URL a relative reference
     *
     * Returns true if the current URL does not begin with a scheme.
     * https://tools.ietf.org/html/rfc3986#section-4.1
     *
     * @return bool
     * @throws Exception
     */
    public function isRelativeReference(): bool
    {
        return $this->scheme() === null;
    }

    /**
     * Resolve a relative reference against the URL of the current instance.
     *
     * That basically means you get an absolute URL from any relative reference (link href, image src, etc.) found on
     * a web page.
     * When the provided input already is an absolute URL, it's just returned as it is (except for validation changes
     * like percent encoding).
     *
     * @param string $relativeUrl
     * @return Url
     * @throws Exception
     */
    public function resolve(string $relativeUrl = ''): Url
    {
        return $this->resolver()->resolve($relativeUrl, $this);
    }

    /**
     * Return true when the current URL contains an internationalized domain name in the host component.
     *
     * @return bool
     */
    public function hasIdn(): bool
    {
        return $this->host instanceof Host && $this->host->hasIdn();
    }

    /**
     * Returns true if the current instance URL is equal to the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isEqualTo(Url|string $url): bool
    {
        return $this->compare($url);
    }

    /**
     * Returns true when some component is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isComponentEqualIn(Url|string $url, string $componentName): bool
    {
        return $this->compare($url, $componentName);
    }

    /**
     * Returns true when the scheme component is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isSchemeEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'scheme');
    }

    /**
     * Returns true when the authority is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isAuthorityEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'authority');
    }

    /**
     * Returns true when the user is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isUserEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'user');
    }

    /**
     * Returns true when the password is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isPasswordEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'password');
    }

    /**
     * Returns true when the user information (both user and password) is the same in the current instance and the
     * URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isUserInfoEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'userInfo');
    }

    /**
     * Returns true when the host component is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isHostEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'host');
    }

    /**
     * Returns true when the registrable domain is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isDomainEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'domain');
    }

    /**
     * Returns true when the domain label is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isDomainLabelEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'domainLabel');
    }

    /**
     * Returns true when the domain suffix is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isDomainSuffixEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'domainSuffix');
    }

    /**
     * Returns true when the subdomain is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isSubdomainEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'subdomain');
    }

    /**
     * Returns true when the port component is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isPortEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'port');
    }

    /**
     * Returns true when the path component is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isPathEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'path');
    }

    /**
     * Returns true when the query component is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isQueryEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'query');
    }

    /**
     * Returns true when the fragment component is the same in the current instance and the URL you want to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isFragmentEqualIn(Url|string $url): bool
    {
        return $this->compare($url, 'fragment');
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->url;
    }

    /**
     * Populate the URL components from an array or another instance of this class
     *
     * This method does no validation so the components coming in via an array need to be valid!
     * Population from another instance is just like cloning, and it's necessary for the PSR-7 UriInterface Adapter
     * class.
     *
     * @param array<string|int>|Url $components
     */
    private function populate(array|Url $components): void
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
     * Parse and validate $url in case it's a string, return when it's an instance of `Url` or throw an Exception.
     *
     * @return array<string|int>
     * @throws InvalidArgumentException
     * @throws InvalidUrlException
     */
    private function validate(string $url): array
    {
        $validComponents = Validator::urlAndComponents($url);

        if (!is_array($validComponents)) {
            throw new InvalidUrlException($url . ' is not a valid URL.');
        }

        return $validComponents;
    }

    private function isValidComponentName(string $componentName): bool
    {
        return in_array($componentName, $this->components, true);
    }

    /**
     * @throws InvalidUrlComponentException
     */
    private function validateComponentValue(string $componentName, string|int $componentValue): string|int
    {
        $validComponentValue = Validator::callValidationByComponentName($componentName, $componentValue);

        if ($validComponentValue === null) {
            throw new InvalidUrlComponentException('Invalid ' . $componentName . '.');
        }

        return $validComponentValue;
    }

    /**
     * Regenerate the full URL after changing components.
     *
     * @throws Exception
     */
    private function updateFullUrl(): void
    {
        $this->url = $this->root() . $this->relative();
    }

    /**
     * @throws Exception
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
     * @throws InvalidUrlComponentException|Exception
     */
    private function validatePathStartsWithSlash(): void
    {
        if ($this->path() && $this->path() !== '' && !Helpers::startsWith($this->path(), '/', 1)) {
            throw new InvalidUrlComponentException(
                'The current path doesn\'t start with a slash which is why an authority component can\'t be ' .
                'added to the URL.'
            );
        }
    }

    private function resolver(): Resolver
    {
        if (!$this->resolver) {
            $this->resolver = new Resolver();
        }

        return $this->resolver;
    }

    /**
     * Compares the current instance with another URL.
     *
     * @param string|null $componentName  Compare either only a certain component of the URLs or the whole URLs if null.
     * @throws InvalidArgumentException
     */
    private function compare(string|Url $compareToUrl, ?string $componentName = null): bool
    {
        if (is_string($compareToUrl)) {
            try {
                $compareToUrl = new Url($compareToUrl);
            } catch (InvalidUrlException) {
                // When the URL to compare is invalid (and thereby has no valid components) it (or any component)
                // can't be equal to this `Url` instance, so return false.
                return false;
            }
        } elseif (!$compareToUrl instanceof Url) {
            throw new InvalidArgumentException('Param must be either string or instance of Crwlr\Url\Url.');
        }

        if ($componentName === null) {
            return $this->toString() === $compareToUrl->toString();
        } elseif ($this->isValidComponentName($componentName)) {
            return $this->{$componentName}() === $compareToUrl->{$componentName}();
        }

        return false;
    }

    /**
     * @return array<string|int>
     * @throws Exception
     */
    private function authorityComponents(): array
    {
        return ['host' => $this->host(), 'user' => $this->user, 'password' => $this->pass, 'port' => $this->port()];
    }

    /**
     * @return string[]
     */
    private function userInfoComponents(): array
    {
        return ['user' => $this->user, 'password' => $this->pass];
    }
}
