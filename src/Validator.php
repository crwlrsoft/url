<?php

namespace Crwlr\Url;

use Crwlr\Url\Exceptions\InvalidUrlException;
use TrueBV\Punycode;

/**
 * Class Validator
 *
 * This class has all the validation logic. It validates a full url or single url components.
 */

class Validator
{
    /**
     * @var Punycode
     */
    private $punyCode;

    /**
     * @param Punycode|null $punyCode
     */
    public function __construct(?Punycode $punyCode = null)
    {
        $this->punyCode = $punyCode ?: Helpers::punyCode();
    }

    /**
     * Validate a url
     *
     * Returns an array with the valid url and all it's valid components separately.
     * Returns null when the input url is invalid.
     *
     * @param string $url
     * @param bool $absoluteUrl  Set to true when only an absolute url should return a valid result.
     * @return array|null
     */
    public function url(string $url = '', bool $absoluteUrl = false): ?array
    {
        if (trim($url) === '') {
            return $absoluteUrl ? null : ['url' => '', 'path' => ''];
        }

        try {
            $url = $this->encodeIdnHostInUrl($url);
        } catch (InvalidUrlException $exception) {
            return null;
        }

        $components = parse_url($url);

        if (
            is_array($components) &&
            !empty($components) &&
            ($absoluteUrl === false || filter_var($url, FILTER_VALIDATE_URL) !== false)
        ) {
            $validComponents = $this->validateComponents($components);

            if (!empty($validComponents)) {
                $validComponents['url'] = Helpers::buildUrlFromComponents($validComponents);

                return $validComponents;
            }
        }

        return null;
    }

    /**
     * Validate an array of url components
     *
     * Returns an empty array when one of the components is invalid.
     *
     * @param array $components
     * @return array
     */
    private function validateComponents(array $components): array
    {
        foreach ($components as $componentName => $componentValue) {
            if (method_exists($this, $componentName)) {
                if ($componentName === 'path') {
                    $hasAuthority = false;

                    if (isset($components['host'])) {
                        $hasAuthority = true;
                    }

                    $validComponent = $this->path($componentValue, $hasAuthority);
                } else {
                    $validComponent = $this->{$componentName}($componentValue);
                }

                if ($validComponent === null) {
                    return [];
                }

                $components[$componentName] = $validComponent;
            }
        }

        return $components;
    }

    /**
     * Validate a scheme
     *
     * Returns the valid lowercase scheme or null when input scheme is invalid.
     *
     * @param string $scheme
     * @return string|null
     */
    public function scheme(string $scheme = ''): ?string
    {
        $scheme = strtolower(trim($scheme));

        if (Helpers::schemes()->exists($scheme)) {
            return $scheme;
        }

        return null;
    }

    /**
     * Validate a user name or password
     *
     * Returns the valid user or password string when it only contains allowed characters
     * https://tools.ietf.org/html/rfc3986#section-3.2.1
     *
     * As this method only validates either a user or a password, the : is not allowed,
     * because it's used to separate user and password.
     *
     * @param string $string
     * @return string|null
     */
    public function userOrPassword(string $string = ''): ?string
    {
        $pattern = '/[^a-zA-Z0-9\-\.\_\~\%\!\$\&\'\(\)\*\+\,\;\=]/';

        if (!preg_match($pattern, $string)) {
            return $string;
        }

        return null;
    }

    /**
     * Validate a host
     *
     * Returns the valid host string or null for invalid host.
     * Internationalized domain names are encoded using Punycode.
     *
     * @param string $host
     * @return string|null
     */
    public function host(string $host): ?string
    {
        if ($this->isNotEmptyString($host)) {
            if (Helpers::containsCharactersNotAllowedInHost($host)) {
                $host = $this->punyCode->encode($host);
            }

            if (!Helpers::containsCharactersNotAllowedInHost($host) && !$this->hostHasEmptyLabel($host)) {
                return $host;
            }
        }

        return null;
    }

    /**
     * Validate a public domain suffix
     *
     * Returns the valid domain suffix or null if invalid.
     * Suffixes of internationalized domain names are encoded using Punycode.
     *
     * @param string $domainSuffix
     * @return string|null
     */
    public function domainSuffix(string $domainSuffix = ''): ?string
    {
        if ($this->isNotEmptyString($domainSuffix)) {
            if (Helpers::containsCharactersNotAllowedInHost($domainSuffix)) {
                $domainSuffix = $this->punyCode->encode($domainSuffix);
            }

            $domainSuffix = strtolower(trim($domainSuffix));

            if (
                !Helpers::containsCharactersNotAllowedInHost($domainSuffix) &&
                Helpers::suffixes()->exists($domainSuffix)
            ) {
                return $domainSuffix;
            }
        }

        return null;
    }

    /**
     * Validate a registrable domain
     *
     * Returns a valid registrable domain or null if invalid.
     * Returns null when a subdomain is included, so don't use this method to validate a host.
     *
     * @param string $domain
     * @param bool $withoutSuffix  Set to true to validate a domain label only, without public domain suffix.
     * @return string|null
     */
    public function domain(string $domain = '', bool $withoutSuffix = false): ?string
    {
        if ($this->isNotEmptyString($domain)) {
            if (Helpers::containsCharactersNotAllowedInHost($domain)) {
                $domain = $this->punyCode->encode($domain);
            }

            $domain = strtolower($domain);

            if ($withoutSuffix === true && !Helpers::containsCharactersNotAllowedInHost($domain, true)) {
                return $domain;
            } elseif ($withoutSuffix === false && !Helpers::containsCharactersNotAllowedInHost($domain)) {
                $suffix = Helpers::suffixes()->getByHost($domain);

                if ($suffix) {
                    // The registrable domain part of the host doesn't contain a subdomain, so if $domain
                    // without the public suffix contains a ".", it's not a valid registrable domain.
                    $domainWithoutSuffix = substr($domain, 0, (strlen($domain) - strlen($suffix) - 1));

                    if ($domainWithoutSuffix !== '' && strpos($domainWithoutSuffix, '.') === false) {
                        return $domain;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate a subdomain
     *
     * Returns the valid subdomain or null if invalid. Disallowed characters are encoded using Punycode.
     *
     * @param string $subdomain
     * @return string|null
     */
    public function subdomain(string $subdomain = ''): ?string
    {
        if ($this->isNotEmptyString($subdomain)) {
            if (Helpers::containsCharactersNotAllowedInHost($subdomain)) {
                $subdomain = $this->punyCode->encode($subdomain);
            }

            $subdomain = strtolower(trim($subdomain));

            if (!Helpers::containsCharactersNotAllowedInHost($subdomain)) {
                return $subdomain;
            }
        }

        return null;
    }

    /**
     * Validate a port
     *
     * Returns the valid port as int or null when port is not in allowed range (0 to 65535).
     *
     * @param int $port
     * @return int|null
     */
    public function port(int $port = 0): ?int
    {
        return $port >= 0 && $port <= 65535 ? $port : null;
    }

    /**
     * Validate path component
     *
     * Returns path string percent-encoded according to https://tools.ietf.org/html/rfc3986#section-3.3
     * or null for an invalid path.
     *
     * When the url doesn't contain an authority component, it can't start with more than one slash.
     * It it doesn't start with a slash (relative-path reference) it must not contain a colon in the first segment.
     *
     * @param string $path
     * @param bool $hasAuthority  Set to false when the uri containing that path has no authority component.
     * @return string|null
     */
    public function path(string $path, bool $hasAuthority = true): ?string
    {
        if (
            $hasAuthority === false &&
            (
                Helpers::startsWith($path, '//', 2) ||
                (!Helpers::startsWith($path, '/', 1) && Helpers::containsXBeforeFirstY($path, ':', '/'))
            )
        ) {
            return null;
        }

        $path = $this->encodePercentCharacter($path);

        return $this->urlEncodeExcept($path, $this->pcharRegexPattern(['/', '%']));
    }

    /**
     * Validate query string
     *
     * Returns query string percent-encoded according to https://tools.ietf.org/html/rfc3986#section-3.4
     * In case PHPs rawurlencode method finds no encoded representation of a character it is removed.
     *
     * @param string $query
     * @return string
     */
    public function query(string $query = ''): string
    {
        if (substr($query, 0, 1) === '?') {
            $query = substr($query, 1);
        }

        $query = $this->encodePercentCharacter($query);

        return $this->urlEncodeExcept($query, $this->pcharRegexPattern(['/', '%']));
    }

    /**
     * Validate fragment component
     *
     * Returns fragment percent-encoded according to https://tools.ietf.org/html/rfc3986#section-3.5
     * In case PHPs rawurlencode method finds no encoded representation of a character it is removed.
     *
     * @param string $fragment
     * @return string
     */
    public function fragment(string $fragment = ''): string
    {
        if (substr($fragment, 0, 1) === '#') {
            $fragment = substr($fragment, 1);
        }

        $fragment = $this->encodePercentCharacter($fragment);

        return $this->urlEncodeExcept($fragment, $this->pcharRegexPattern(['/', '?', '%']));
    }

    /**
     * Url encode all characters except those from a certain regex pattern
     *
     * @param string $encode
     * @param string $exceptRegexPattern
     * @return string
     */
    private function urlEncodeExcept(string $encode, string $exceptRegexPattern): string
    {
        return preg_replace_callback(
            $exceptRegexPattern,
            function($match) {
                return rawurlencode($match[0]);
            },
            $encode
        );
    }

    /**
     * Return the regex pattern for pchar (optionally plus additional characters)
     *
     * https://tools.ietf.org/html/rfc3986#appendix-A
     *
     * @param array $additionalCharacters
     * @return string
     */
    private function pcharRegexPattern(array $additionalCharacters = []): string
    {
        $pattern = "/[^a-zA-Z0-9-._~!$&\'()*+,;=:@";

        foreach ($additionalCharacters as $character) {
            //$pattern .= "\\" . $character;
            $pattern .= preg_quote($character, '/');
        }

        return $pattern . "]/";
    }

    /**
     * Encode percent character in path, query or fragment
     *
     * If the string (path, query, fragment) contains a percent character that is not part of an already percent
     * encoded character it must be encoded (% => %25). So this method replaces all percent characters that are not
     * followed by a hex code.
     *
     * @param string $string
     * @return string
     */
    private function encodePercentCharacter(string $string = ''): string
    {
        return preg_replace('/%(?![0-9A-Fa-f][0-9A-Fa-f])/', '%25', $string) ?: $string;
    }

    /**
     * Encode internationalized domain names using Punycode in a url
     *
     * PHPs parse_url method breaks special characters in internationalized domain names. So this method
     * uses the getHostFromIdnUrl method below to find the host part, checks for not allowed characters and handles
     * encoding if needed.
     *
     * @param string $url
     * @return string
     * @throws InvalidUrlException
     */
    private function encodeIdnHostInUrl(string $url = ''): string
    {
        $host = $this->getHostFromIdnUrl($url);

        if (is_string($host) && $host !== '' && Helpers::containsCharactersNotAllowedInHost($host)) {
            $encodedHost = $this->punyCode->encode($host);
            $hostPositionInUrl = strpos($url, $host);
            $preHost = substr($url, 0, $hostPositionInUrl);
            $postHost = substr($url, ($hostPositionInUrl + strlen($host)));
            $url = $preHost . $encodedHost . $postHost;
        }

        return $url;
    }

    /**
     * Manually find the host part in a url
     *
     * PHPs parse_url method breaks special characters in internationalized domain names.
     * This method manually extracts the host component from a url (if exists) without breaking special characters.
     *
     * @param string $url
     * @return string|null
     * @see Validator::encodeIdnHostInUrl()
     * @throws InvalidUrlException
     */
    private function getHostFromIdnUrl(string $url = ''): ?string
    {
        $firstTwoChars = substr($url, 0, 2);

        if (substr($url, 0, 1) === '/' && $firstTwoChars !== '//') { // It's a relative reference (path).
            return null;
        } elseif ($firstTwoChars === '//') { // Protocol relative like //www.example.com/path
            $urlWithoutScheme = $url;
        } else {
            $urlWithoutScheme = $this->stripSchemeFromIdnUrl($url);

            if ($url === $urlWithoutScheme) {
                throw new InvalidUrlException('Url neither starts with "/" nor contains a scheme.');
            }
        }

        $splitAtSlash = explode('/', $urlWithoutScheme);

        foreach ($splitAtSlash as $part) {
            if ($part !== '') {
                return $part;
            }
        }

        return null;
    }

    /**
     * Manually strip the scheme part from a url
     *
     * Helper method for getHostFromIdnUrl method.
     *
     * @param string $url
     * @return string
     * @see Validator::getHostFromIdnUrl()
     */
    private function stripSchemeFromIdnUrl(string $url = ''): string
    {
        $splitAtColon = explode(':', $url);

        if (count($splitAtColon) === 1) {
            return $url;
        }

        unset($splitAtColon[0]);

        return implode(':', $splitAtColon);
    }

    /**
     * Check for empty label parts in a host component
     *
     * Check for empty parts when splitting the host string at '.' (.com or www..com => invalid).
     * https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @param string $host
     * @return bool
     */
    private function hostHasEmptyLabel(string $host = ''): bool
    {
        foreach (explode('.', $host) as $label) {
            if (trim($label) === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if $string is of type string and is not empty (whitespace trimmed).
     *
     * @param $string
     * @return bool
     */
    private function isNotEmptyString($string): bool
    {
        if (is_string($string) && trim($string) !== '') {
            return true;
        }

        return false;
    }
}
