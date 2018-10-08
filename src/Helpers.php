<?php

namespace Crwlr\Url;

use TrueBV\Punycode;

class Helpers
{
    /**
     * @var Suffixes
     */
    private static $suffixes;

    /**
     * @var Schemes
     */
    private static $schemes;

    /**
     * @var Punycode
     */
    private static $punyCode;

    /**
     * Get an instance of the Suffixes class.
     *
     * @return Suffixes
     */
    public static function suffixes() : Suffixes
    {
        if (!self::$suffixes instanceof Suffixes) {
            self::$suffixes = new Suffixes(self::punyCode());
        }

        return self::$suffixes;
    }

    /**
     * Get an instance of the Schemes class.
     *
     * @return Schemes
     */
    public static function schemes() : Schemes
    {
        if (!self::$schemes instanceof Schemes) {
            self::$schemes = new Schemes();
        }

        return self::$schemes;
    }

    /**
     * Get an instance of the Punycode class.
     *
     * @return Punycode
     */
    public static function punyCode() : Punycode
    {
        if (!self::$punyCode instanceof Punycode) {
            self::$punyCode = new Punycode();
        }

        return self::$punyCode;
    }

    /**
     * @param string $host
     * @return string
     */
    public static function encodeHost(string $host): string
    {
        if (self::containsCharactersNotAllowedInHost($host)) {
            return self::punyCode()->encode($host);
        }

        return $host;
    }

    /**
     * Builds a url from an array of url components. It doesn't do any validation and assumes the provided component
     * values are valid.
     *
     * @param array $comp
     * @return string
     */
    public static function buildUrlFromComponents(array $comp = []) : string
    {
        $url = '';

        if (isset($comp['scheme'])) {
            $url .= $comp['scheme'] . ':';

            if (isset($comp['port']) && $comp['port'] === self::getStandardPortByScheme($comp['scheme'])) {
                unset($comp['port']);
            }
        }

        $url .= isset($comp['host']) ? '//' : '';

        if (isset($comp['user'])) {
            $url .= $comp['user'] . (isset($comp['pass']) ? ':' . $comp['pass'] : '') . '@';
        }

        $url .= $comp['host'] . (isset($comp['port']) ? ':' . $comp['port'] : '');

        $url .= $comp['path'] ?? '';
        $url .= isset($comp['query']) ? '?' . $comp['query'] : '';
        $url .= isset($comp['fragment']) ? '#' . $comp['fragment'] : '';

        return $url;
    }

    /**
     * Converts a url query string to array.
     *
     * @param string $query
     * @return array
     */
    public static function queryStringToArray(string $query = '') : array
    {
        parse_str($query, $array);

        if (preg_match('/(?:^|&)([^\[=&]*\.)/', $query)) { // Matches keys in the query that contain a dot
            return self::replaceKeysContainingDots($query, $array);
        }

        return $array;
    }

    /**
     * Try to get the standard port of a url scheme using PHP's built-in getservbyname() function.
     * If no standard port is found it returns null.
     *
     * @param string $scheme
     * @return int|null
     */
    public static function getStandardPortByScheme(string $scheme)
    {
        $scheme = strtolower(trim($scheme));

        if ($scheme === '') {
            return null;
        }

        $standardPortTcp = getservbyname($scheme, 'tcp');

        if ($standardPortTcp) {
            return (int) $standardPortTcp;
        }

        $standardPortUdp = getservbyname($scheme, 'udp');

        if ($standardPortUdp) {
            return (int) $standardPortUdp;
        }

        return null;
    }

    /**
     * @param string $string
     * @param bool $noDot
     * @return bool
     */
    public static function containsCharactersNotAllowedInHost(string $string, bool $noDot = false) : bool
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
     * Strip some string B from the end of a string A that ends with string B.
     * e.g.:
     * $string = 'some.example'
     * $strip = '.example'
     * => 'some'
     *
     * @param string $string
     * @param string $strip
     * @return string
     */
    public static function stripFromEnd(string $string = '', string $strip = '') : string
    {
        $stripLength = strlen($strip);
        $stringLength = strlen($string);

        if ($stripLength > $stringLength) {
            return $string;
        }

        $endOfString = substr($string, ($stringLength - $stripLength));

        if ($endOfString === $strip) {
            return substr($string, 0, (strlen($string) - strlen($strip)));
        }

        return $string;
    }

    /**
     * When keys within a url query string contain dots, PHPs parse_str method converts them to underscores. This
     * method works around this issue so the requested query array returns the proper keys with dots.
     *
     * @param string $query
     * @param array $array
     * @return array
     */
    private static function replaceKeysContainingDots(string $query, array $array) : array
    {
        // Regex to find keys in query string.
        preg_match_all('/(?:^|&)([^=&\[]+)(?:[=&\[]|$)/', $query, $matches);
        $brokenKeys = $fixedArray = [];

        // Create mapping of broken keys to original proper keys.
        foreach ($matches[1] as $key => $value) {
            if (strpos($value, '.') !== false) {
                $brokenKeys[str_replace('.', '_', $value)] = $value;
            }
        }

        // Recreate the array with the proper keys.
        foreach ($array as $key => $value) {
            if (isset($brokenKeys[$key])) {
                $fixedArray[$brokenKeys[$key]] = $value;
            } else {
                $fixedArray[$key] = $value;
            }
        }

        return $fixedArray;
    }
}
