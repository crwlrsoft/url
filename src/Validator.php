<?php

namespace Crwlr\Url;

use Crwlr\Url\Exceptions\InvalidUrlException;

/**
 * Class Validator
 *
 * This class has all the validation logic. It validates a full url or single url components.
 */

class Validator
{
    /**
     * Validate a url
     *
     * Returns an array with the valid url and all it's valid components separately.
     * Returns null when the input url is invalid.
     *
     * @param string $url
     * @return array|null
     */
    public static function url(string $url = ''): ?array
    {
        if (trim($url) === '') {
            return ['url' => '', 'path' => ''];
        }

        return self::returnValidUrlAndComponentsArray(self::getValidComponents($url));
    }

    /**
     * Validate an absolute url
     *
     * Returns an array with the valid url and all it's valid components separately.
     * Returns null when the input url is invalid or not an absolute url.
     *
     * @param string $url
     * @return array|null
     */
    public static function absoluteUrl(string $url): ?array
    {
        if (trim($url) === '') {
            return null;
        }

        return self::returnValidUrlAndComponentsArray(self::getValidComponents($url, true));
    }

    /**
     * Validate a scheme
     *
     * Returns the valid lowercase scheme or null when input scheme is invalid.
     *
     * @param string $scheme
     * @return string|null
     */
    public static function scheme(string $scheme = ''): ?string
    {
        $scheme = strtolower(trim($scheme));

        if (Helpers::schemes()->exists($scheme)) {
            return $scheme;
        }

        return null;
    }

    /**
     * Validate (only) the user from the user info
     *
     * @param string $user
     * @return string|null
     */
    public static function user(string $user): ?string
    {
        return self::userOrPassword($user);
    }

    /**
     * Validate (only) the password from the user info
     *
     * @param string $password
     * @return string|null
     */
    public static function password(string $password): ?string
    {
        return self::userOrPassword($password);
    }

    /**
     * Alias for method password
     *
     * @param string $pass
     * @return string|null
     */
    public static function pass(string $pass): ?string
    {
        return self::password($pass);
    }

    /**
     * Validate a user name or password
     *
     * Percent encodes characters that aren't allowed within a user information component.
     *
     * As this method only validates either a user or a password, the : is not allowed, because it's used to separate
     * user and password.
     *
     * @param string $string
     * @return string|null
     */
    private static function userOrPassword(string $string = ''): ?string
    {
        $string = self::encodePercentCharacter($string);

        return self::urlEncodeExcept($string, "/[^a-zA-Z0-9-._~!$&'() * +,;=%]/");
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
    public static function host(string $host): ?string
    {
        if (trim($host) !== '') {
            if (Validator::containsCharactersNotAllowedInHost($host)) {
                $host = Helpers::punyCode()->encode($host);
            }

            if (!Validator::containsCharactersNotAllowedInHost($host) && !self::hostHasEmptyLabel($host)) {
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
    public static function domainSuffix(string $domainSuffix = ''): ?string
    {
        if (trim($domainSuffix) !== '') {
            if (Validator::containsCharactersNotAllowedInHost($domainSuffix)) {
                $domainSuffix = Helpers::punyCode()->encode($domainSuffix);
            }

            $domainSuffix = strtolower(trim($domainSuffix));

            if (
                !Validator::containsCharactersNotAllowedInHost($domainSuffix) &&
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
    public static function domain(string $domain = '', bool $withoutSuffix = false): ?string
    {
        if (trim($domain) !== '') {
            if (Validator::containsCharactersNotAllowedInHost($domain)) {
                $domain = Helpers::punyCode()->encode($domain);
            }

            $domain = strtolower($domain);

            if ($withoutSuffix === true && !Validator::containsCharactersNotAllowedInHost($domain, true)) {
                return $domain;
            } elseif ($withoutSuffix === false && !Validator::containsCharactersNotAllowedInHost($domain)) {
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
    public static function subdomain(string $subdomain = ''): ?string
    {
        if (trim($subdomain) !== '') {
            if (Validator::containsCharactersNotAllowedInHost($subdomain)) {
                $subdomain = Helpers::punyCode()->encode($subdomain);
            }

            $subdomain = strtolower(trim($subdomain));

            if (!Validator::containsCharactersNotAllowedInHost($subdomain)) {
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
    public static function port(int $port = 0): ?int
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
    public static function path(string $path, bool $hasAuthority = true): ?string
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

        $path = self::encodePercentCharacter($path);

        return self::urlEncodeExcept($path, self::pcharRegexPattern(['/', '%']));
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
    public static function query(string $query = ''): string
    {
        if (substr($query, 0, 1) === '?') {
            $query = substr($query, 1);
        }

        $query = self::encodePercentCharacter($query);

        return self::urlEncodeExcept($query, self::pcharRegexPattern(['/', '%']));
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
    public static function fragment(string $fragment = ''): string
    {
        if (substr($fragment, 0, 1) === '#') {
            $fragment = substr($fragment, 1);
        }

        $fragment = self::encodePercentCharacter($fragment);

        return self::urlEncodeExcept($fragment, self::pcharRegexPattern(['/', '?', '%']));
    }

    /**
     * Get all valid url components from the provided url string as array.
     *
     * In case of an invalid url null is returned.
     *
     * @param string $url
     * @param bool $onlyAbsoluteUrl  When set to true, it will also return null when the input is a relative reference.
     * @return array|null
     */
    private static function getValidComponents(string $url, bool $onlyAbsoluteUrl = false): ?array
    {
        try {
            $url = self::encodeIdnHostInUrl($url);
        } catch (InvalidUrlException $exception) {
            return null;
        }

        $components = parse_url($url);

        if (
            is_array($components) &&
            !empty($components) &&
            ($onlyAbsoluteUrl === false || filter_var($url, FILTER_VALIDATE_URL) !== false)
        ) {
            $validComponents = self::validateComponents($components);

            if (!empty($validComponents)) {
                return $validComponents;
            }
        }

        return null;
    }

    /**
     * Validate an array of url components.
     *
     * Returns an empty array when one of the components is invalid.
     *
     * @param array $components
     * @return array
     */
    private static function validateComponents(array $components): array
    {
        foreach ($components as $componentName => $componentValue) {
            if (method_exists(Validator::class, $componentName)) {
                if ($componentName === 'path') {
                    $validComponent = self::path($componentValue, isset($components['host']) ? true : false);
                } else {
                    $validComponent = self::{$componentName}($componentValue);
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
     * Helper method for the url and absoluteUrl methods.
     *
     * Because it's the same for both methods.
     *
     * @param array|null $validComponents
     * @return array|null
     */
    private static function returnValidUrlAndComponentsArray(?array $validComponents): ?array
    {
        if (!$validComponents) {
            return null;
        }

        $validComponents['url'] = Helpers::buildUrlFromComponents($validComponents);

        return $validComponents;
    }

    /**
     * Check if string contains characters not allowed in the host component.
     *
     * @param string $string
     * @param bool $noDot  Set to true when dot should not be allowed (e.g. checking only domain label).
     * @return bool
     */
    private static function containsCharactersNotAllowedInHost(string $string, bool $noDot = false): bool
    {
        $pattern = '/[^a-zA-Z0-9\-\.]/';

        if ($noDot === true) {
            $pattern = '/[^a-zA-Z0-9\-]/';
        }

        if (preg_match($pattern, $string)) {
            return true;
        }

        return false;
    }

    /**
     * Url encode all characters except those from a certain regex pattern
     *
     * @param string $encode
     * @param string $exceptRegexPattern
     * @return string
     */
    private static function urlEncodeExcept(string $encode, string $exceptRegexPattern): string
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
    private static function pcharRegexPattern(array $additionalCharacters = []): string
    {
        $pattern = "/[^a-zA-Z0-9-._~!$&\'()*+,;=:@";

        foreach ($additionalCharacters as $character) {
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
    private static function encodePercentCharacter(string $string = ''): string
    {
        return preg_replace('/%(?![0-9A-Fa-f][0-9A-Fa-f])/', '%25', $string) ?: $string;
    }

    /**
     * Encode internationalized domain names using Punycode in a url
     *
     * PHPs parse_url method breaks special characters in internationalized domain names. So this method
     * uses the getAuthorityFromUrl method below to find the host part, checks for not allowed characters and handles
     * encoding if needed.
     *
     * @param string $url
     * @return string
     * @throws InvalidUrlException
     */
    private static function encodeIdnHostInUrl(string $url = ''): string
    {
        $authority = self::getAuthorityFromUrl($url);

        if ($authority === null || !Validator::containsCharactersNotAllowedInHost($authority)) {
            return $url;
        }

        $userInfo = self::getUserInfoFromAuthority($authority);

        if ($userInfo !== '') {
            $authority = Helpers::stripFromStart($authority, $userInfo . '@');
        }

        $host = self::stripPortFromAuthority($authority);

        if (is_string($host) && $host !== '' && Validator::containsCharactersNotAllowedInHost($host)) {
            $encodedHost = Helpers::punyCode()->encode($host);
            $url = Helpers::replaceFirstOccurrence($host, $encodedHost, $url);
        }

        return $url;
    }

    /**
     * Manually find the authority part in a url
     *
     * PHPs parse_url method breaks special characters in internationalized domain names.
     * This method manually extracts the authority component from a url (if exists) without breaking special characters.
     *
     * @param string $url
     * @return string|null
     * @see Validator::encodeIdnHostInUrl()
     * @throws InvalidUrlException
     */
    private static function getAuthorityFromUrl(string $url = ''): ?string
    {
        $firstTwoChars = substr($url, 0, 2);

        if (substr($url, 0, 1) === '/' && $firstTwoChars !== '//') { // It's a relative reference (path).
            return null;
        } elseif ($firstTwoChars === '//') { // Protocol relative like //www.example.com/path
            $urlWithoutScheme = $url;
        } else {
            $urlWithoutScheme = self::stripSchemeFromUrl($url);

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
     * Get the user info part from an authority.
     *
     * @param string $authority
     * @return string
     */
    private static function getUserInfoFromAuthority(string $authority): string
    {
        if (strpos($authority, '@') !== false) {
            $splitAtAt = explode('@', $authority);

            if (count($splitAtAt) > 1) {
                return Helpers::stripFromEnd($authority, '@' . end($splitAtAt));
            }
        }

        return '';
    }

    /**
     * Strip the port at the end of an authority if there is one.
     *
     * @param string $authority
     * @return string
     */
    private static function stripPortFromAuthority(string $authority): string
    {
        $splitAtColon = explode(':', $authority);

        if (count($splitAtColon) > 1) {
            $potentialPort = end($splitAtColon);

            if (is_numeric($potentialPort)) {
                return Helpers::stripFromEnd($authority, ':' . $potentialPort);
            }
        }

        return $authority;
    }

    /**
     * Manually strip the scheme part from a url
     *
     * Helper method for getAuthorityFromUrl method.
     *
     * @param string $url
     * @return string
     * @see Validator::getAuthorityFromUrl()
     */
    private static function stripSchemeFromUrl(string $url = ''): string
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
    private static function hostHasEmptyLabel(string $host = ''): bool
    {
        foreach (explode('.', $host) as $label) {
            if (trim($label) === '') {
                return true;
            }
        }

        return false;
    }
}
