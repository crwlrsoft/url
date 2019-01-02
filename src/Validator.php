<?php

namespace Crwlr\Url;

use Crwlr\Url\Exceptions\InvalidUrlException;
use TrueBV\Punycode;

/**
 * Class Validator
 *
 * This class has all the validation logic. It validates a full url and also single url components.
 * The methods always return the validated value or false, because it may make some changes to the input like encoding
 * internationalized domain names. So always use the returned values and don't just check if it doesn't return false.
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
    public function __construct(Punycode $punyCode = null)
    {
        $this->punyCode = ($punyCode instanceof Punycode) ? $punyCode : new Punycode();
    }

    /**
     * Validates a url.
     * Returns an array with the valid url and all it's valid components separately.
     * Returns null when url is invalid.
     *
     * @param string $url
     * @param bool $absoluteUrl  Set to true when only an absolute url should return a valid result.
     * @return array|null
     */
    public function url(string $url = '', bool $absoluteUrl = false)
    {
        if (trim($url) === '') {
            if ($absoluteUrl) {
                return null;
            }

            return ['url' => '', 'path' => ''];
        }

        if (substr($url, 0, 4) === 'www.') {
            $url = '//' . $url;
        }

        $components = parse_url($url);

        if (is_array($components) && array_key_exists('host', $components)) {
            try {
                $url = $this->encodeIdnHostInUrl($url);
                $components = parse_url($url);
            } catch (InvalidUrlException $exception) {
                return null;
            }
        }

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
     * @param array $components
     * @return array
     */
    private function validateComponents(array $components) : array
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

                if ($validComponent === false) {
                    return [];
                }

                $components[$componentName] = $validComponent;
            }
        }

        return $components;
    }

    /**
     * If $scheme is a valid url scheme (contained in the IANA list) the scheme is returned (lowercase),
     * otherwise it returns false.
     *
     * @param string $scheme
     * @return string|false
     */
    public function scheme(string $scheme = '')
    {
        $scheme = strtolower(trim($scheme));

        if (Helpers::schemes()->exists($scheme)) {
            return $scheme;
        }

        return false;
    }

    /**
     * Returns the user name or password if $string only contains unreserved, percent-encoded
     * or sub-delimiter characters according to https://tools.ietf.org/html/rfc3986#section-3.2.1
     * As this method only validates either a user or a password, the : is not allowed, because
     * it's used to separate user and password.
     *
     * @param string $string
     * @return string|false
     */
    public function userOrPassword(string $string = '')
    {
        $pattern = '/[^a-zA-Z0-9\-\.\_\~\%\!\$\&\'\(\)\*\+\,\;\=]/';

        if (!preg_match($pattern, $string)) {
            return $string;
        }

        return false;
    }

    /**
     * Returns the valid host name if the string only contains characters allowed within
     * a host name (if IDN it will be encoded), has no empty label (e.g. "www..com") and a domain suffix
     * that is contained in the Mozilla Public Suffix List.
     *
     * @param string $host
     * @return string|false
     */
    public function host(string $host)
    {
        if ($this->isNotEmptyString($host)) {
            if (Helpers::containsCharactersNotAllowedInHost($host)) {
                $host = $this->punyCode->encode($host);
            }

            if (!Helpers::containsCharactersNotAllowedInHost($host) && !$this->hostHasEmptyLabel($host)) {
                return $host;
            }
        }

        return false;
    }

    /**
     * Returns the valid domain suffix if it exists.
     * Also tries to encode characters from internationalized domain names to validate the suffix.
     *
     * @param string $domainSuffix
     * @return string|false
     */
    public function domainSuffix(string $domainSuffix = '')
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

        return false;
    }

    /**
     * Returns a valid registrable domain name if $domain consists of characters that are valid in a
     * host name, ends with a public domain suffix, and does not contain a subdomain.
     * If you set $withoutSuffix to true it checks if it would be a valid registrable domain if it had
     * a valid public domain suffix.
     *
     * @param string $domain
     * @param bool $withoutSuffix
     * @return string|false
     */
    public function domain(string $domain = '', bool $withoutSuffix = false)
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

        return false;
    }

    /**
     * Returns the valid subdomain if $subdomain is not empty and consists exclusively of characters
     * that are valid within a host name.
     *
     * @param string $subdomain
     * @return string|false
     */
    public function subdomain(string $subdomain = '')
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

        return false;
    }

    /**
     * Returns $port as int if it is numeric and between 0 and 65535.
     *
     * @param int|string $port
     * @return int|false
     */
    public function port($port = 0)
    {
        if (is_numeric($port)) {
            $port = (int) $port;

            if ($port >= 0 && $port <= 65535) {
                return $port;
            }
        }

        return false;
    }

    /**
     * Percent-encodes any character, that is not an unreserved, sub-delim, : or @ according to
     * https://tools.ietf.org/html/rfc3986#section-3.3
     * In case rawurlencode does not return a percent-encoded equivalent the character will be removed.
     *
     * @param string $path
     * @param bool $hasAuthority
     * @return string|false
     */
    public function path(string $path, bool $hasAuthority = true)
    {
        if (
            ($hasAuthority === true && trim($path) !== '' && substr($path, 0, 1) !== '/') ||
            ($hasAuthority !== true && substr($path, 0, 2) === '//')
        ) {
            return false;
        }

        if ($hasAuthority === false && substr($path, 0, 1) !== '/') {
            $splitAtSlash = explode('/', $path);

            if (strpos($splitAtSlash[0], ':') !== false) {
                return false;
            }
        }

        $path = preg_replace_callback('/[^a-zA-Z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@\/]/', function ($match) {
            return $this->urlEncodeCharacter($match[0]);
        }, $path);

        return $path;
    }

    /**
     * Percent-encodes any character that needs to be in a query string according to
     * https://tools.ietf.org/html/rfc3986#section-3.4
     * In case rawurlencode does not return a percent-encoded equivalent the character will be removed.
     *
     * @param string $query
     * @return string
     */
    public function query(string $query = '') : string
    {
        if (substr($query, 0, 1) === '?') {
            $query = substr($query, 1);
        }

        return preg_replace_callback('/[^a-zA-Z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@\/\[\]]/', function ($match) {
            return $this->urlEncodeCharacter($match[0]);
        }, $this->encodeBracketsInQuery($query));
    }

    /**
     * Returns the valid $fragment if it consists of characters that are valid within a fragment.
     * https://tools.ietf.org/html/rfc3986#section-3.5
     *
     * @param string $fragment
     * @return string|false
     */
    public function fragment(string $fragment = '')
    {
        if (substr($fragment, 0, 1) === '#') {
            $fragment = substr($fragment, 1);
        }

        $fragment = preg_replace_callback('/[^a-zA-Z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@\/\?]/', function ($match) {
            return $this->urlEncodeCharacter($match[0]);
        }, $fragment);

        return $fragment;
    }

    /**
     * @param string $character
     * @return string
     */
    private function urlEncodeCharacter(string $character = '') : string
    {
        $encodedCharacter = rawurlencode($character);

        if ($character !== $encodedCharacter) {
            return $encodedCharacter;
        }

        return '';
    }

    /**
     * Validating a url with an Internationalized domain name with filter_var() returns false, so the
     * host part in the url needs to be encoded first. parse_url may break special characters in the IDN
     * host name, so extracting the host from the url is done manually in getHostFromIdnUrl().
     *
     * @param string $url
     * @return string
     * @throws InvalidUrlException
     */
    private function encodeIdnHostInUrl(string $url = '') : string
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
     * @param string $url
     * @return string|false
     * @throws InvalidUrlException
     */
    private function getHostFromIdnUrl(string $url = '')
    {
        $firstTwoChars = substr($url, 0, 2);

        if (substr($url, 0, 1) === '/' && $firstTwoChars !== '//') {
            return false;
        } elseif ($firstTwoChars === '//') {
            $urlWithoutScheme = $url;
        } else {
            $urlWithoutScheme = $this->stripSchemeFromIdnUrl($url);
        }

        $splitAtSlash = explode('/', $urlWithoutScheme);

        foreach ($splitAtSlash as $part) {
            if ($part !== '') {
                return $part;
            }
        }

        return false;
    }

    /**
     * @param string $url
     * @return string|false
     * @throws InvalidUrlException
     */
    private function stripSchemeFromIdnUrl(string $url = '')
    {
        $splitAtColon = explode(':', $url);

        if (count($splitAtColon) === 1) {
            throw new InvalidUrlException('Url does not start with / and also does not include a scheme component.');
        }

        unset($splitAtColon[0]);

        return implode(':', $splitAtColon);
    }

    /**
     * Check for empty parts when splitting the host string at '.', because something like
     * '.com' or 'www..com' is not a valid host, but it consists of characters that are valid
     * within a host name and they have a valid domain suffix.
     *
     * @param string $host
     * @return bool
     */
    private function hostHasEmptyLabel(string $host = '') : bool
    {
        foreach (explode('.', $host) as $label) {
            if (trim($label) === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $query
     * @return string
     */
    public function encodeBracketsInQuery(string $query) : string
    {
        if (strpos($query, '[') !== false) {
            list($keyValues, $keyPartsWithoutBrackets, $keyPartsContainingBrackets) = $this->splitQuery($query);

            foreach ($keyValues as $index => $keyValue) {
                if (isset($keyPartsContainingBrackets[$index]) && $keyPartsContainingBrackets[$index] !== '') {
                    $start = $keyPartsWithoutBrackets[$index];
                    $replacement = str_replace(['[', ']'], ['%5B', '%5D'], $keyPartsContainingBrackets[$index]);
                    $end = substr($keyValues[$index], strlen($start) + strlen($keyPartsContainingBrackets[$index]));
                    $keyValues[$index] = $start . $replacement . $end;
                }
            }

            $query = '';

            foreach ($keyValues as $keyValue) {
                $query .= $keyValue;
            }
        }

        return $query;
    }

    /**
     * This helper method is for usage in the encodeBracketsInQuery() method above and splits a query string
     * into an array using preg_match_all().
     *
     * From the resulting array, index 0 contains all full matches
     * (=> ['key1=value1', 'key2=value2', 'key]that[Contains[Useless[Brackets=value3'])
     *
     * index 1 contains all keys
     * (=> ['key1', 'key2[stuff]', 'key]that[Contains[Useless[Brackets'])
     *
     * index 2 contains parts of the keys that contain brackets that should be encoded
     * (=> ['', '', ']that[Contains[Useless[Brackets'])
     *
     * index 3 contains all values
     * (=> ['value1', 'value2', 'value3'])
     *
     * @param string $query
     * @return array
     */
    private function splitQuery(string $query) : array
    {
        preg_match_all(
            '/' .
            '(?:' .
            '([^=&\[]+)' .              // Any character that isn't '=', '&' or '['
            '(?:' .
            '(?:\[[^=&\[\]]*\])*' .     // Either array syntax [indexName1][indexName2]...
            '|' .
            '([^=&]*)' .                // Or any character that isn't '=' or '&'
            ')' .
            ')' .
            '(?:\=(?:[^&]*|$))?' .      // Optional: '=' and possibly a value
            '(?:&|$)' .                 // Either & or end of string
            '/',
            $query,
            $splitQuery
        );

        return $splitQuery;
    }

    /**
     * Returns true if $string is of type string and is not empty (whitespace trimmed).
     *
     * @param $string
     * @return bool
     */
    private function isNotEmptyString($string) : bool
    {
        if (is_string($string) && trim($string) !== '') {
            return true;
        }

        return false;
    }
}
