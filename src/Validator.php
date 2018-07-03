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
     * @var Suffixes
     */
    private $suffixes;

    /**
     * @var Schemes
     */
    private $schemes;

    /**
     * @var Punycode
     */
    private $punyCode;

    /**
     * @param Suffixes|null $suffixes
     * @param Schemes|null $schemes
     * @param Punycode|null $punyCode
     */
    public function __construct(Suffixes $suffixes = null, Schemes $schemes = null, Punycode $punyCode = null)
    {
        $this->suffixes = ($suffixes instanceof Suffixes) ? $suffixes : new Suffixes();
        $this->schemes = ($schemes instanceof Schemes) ? $schemes : new Schemes();
        $this->punyCode = ($punyCode instanceof Punycode) ? $punyCode : new Punycode();
    }

    /**
     * Validates a url, throws an Exception when url is invalid.
     *
     * @param string $url
     * @return string
     * @throws InvalidUrlException
     */
    public function url($url = '') : string
    {
        if (!$this->isNotEmptyString($url)) {
            throw new InvalidUrlException('Empty url.');
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $url = str_replace(' ', '%20', trim($url));
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $url = $this->encodeIdnHostInUrl($url);
        }

        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            // filter_var() doesn't check for a valid url scheme, so validate if it has one.
            $splitAtColon = explode(':', $url);

            if (count($splitAtColon) > 1 && $this->scheme($splitAtColon[0])) {
                return $url;
            }
        }

        throw new InvalidUrlException($url . ' is not a valid url.');
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

        if ($this->schemes->exists($scheme)) {
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
            if (!$this->areValidHostCharacters($host)) {
                $host = $this->punyCode->encode($host);
            }

            if ($this->areValidHostCharacters($host) && !$this->hostHasEmptyLabel($host)) {
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
            if (!$this->areValidHostCharacters($domainSuffix)) {
                $domainSuffix = $this->punyCode->encode($domainSuffix);
            }

            $domainSuffix = strtolower(trim($domainSuffix));

            if ($this->areValidHostCharacters($domainSuffix) && $this->suffixes->exists($domainSuffix)) {
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
            if (!$this->areValidHostCharacters($domain)) {
                $domain = $this->punyCode->encode($domain);
            }

            $domain = strtolower(trim($domain));

            if ($withoutSuffix === true && $this->areValidHostCharacters($domain, true)) {
                return $domain;
            } elseif ($withoutSuffix === false && $this->areValidHostCharacters($domain)) {
                $suffix = $this->suffixes->getByHost($domain);

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
            if (!$this->areValidHostCharacters($subdomain)) {
                $subdomain = $this->punyCode->encode($subdomain);
            }

            $subdomain = strtolower(trim($subdomain));

            if ($this->areValidHostCharacters($subdomain)) {
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
     * @return string
     */
    public function path(string $path) : string
    {
        $path = preg_replace_callback('/[^a-zA-Z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@\/]/', function ($match) {
            $encodedCharacter = rawurlencode($match[0]);

            if ($match[0] !== $encodedCharacter) {
                return $encodedCharacter;
            }

            return '';
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

        $query = preg_replace_callback('/[^a-zA-Z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@\/]/', function ($match) {
            $encodedCharacter = rawurlencode($match[0]);

            if ($match[0] !== $encodedCharacter) {
                return $encodedCharacter;
            }

            return '';
        }, $query);

        return $query;
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

        return $this->queryOrFragment($fragment);
    }

    /**
     * Query and fragment allow the same characters (pchar + / + ?), so we can validate with the same regex.
     *
     * @param string $string
     * @return string|false
     */
    private function queryOrFragment(string $string = '')
    {
        if (!preg_match('/[^a-zA-Z0-9\-\.\_\~\%\!\$\&\'\(\)\*\+\,\;\=\:\@\/\?]/', $string)) {
            return $string;
        }

        return false;
    }

    /**
     * Returns true if $string only contains characters that are allowed within a host.
     *
     * @param string $string
     * @param bool $noDot
     * @return bool
     */
    private function areValidHostCharacters(string $string = '', bool $noDot = false) : bool
    {
        $pattern = '/[^a-zA-Z0-9\-\.]/';

        if ($noDot === true) {
            $pattern = '/[^a-zA-Z0-9\-]/';
        }

        if ($this->isNotEmptyString($string) && !preg_match($pattern, $string)) {
            return true;
        }

        return false;
    }

    /**
     * Validating a url with an Internationalized domain name with filter_var() returns false, so the
     * host part in the url needs to be encoded first. parse_url may break special characters in the IDN
     * host name, so extracting the host from the url is done manually in getHostFromIdnUrl().
     *
     * @param string $url
     * @return string
     */
    private function encodeIdnHostInUrl(string $url = '') : string
    {
        $host = $this->getHostFromIdnUrl($url);

        if (is_string($host) && $host !== '' && strpos($url, $host) !== false) {
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
     */
    private function getHostFromIdnUrl(string $url = '')
    {
        $urlWithoutScheme = $this->stripSchemeFromIdnUrl($url);

        if ($urlWithoutScheme === false) {
            return false;
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
     */
    private function stripSchemeFromIdnUrl(string $url = '')
    {
        $splitAtColon = explode(':', $url);

        if (count($splitAtColon) === 1) {
            return false;
        }

        unset($splitAtColon[0]);
        return implode('', $splitAtColon);
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
