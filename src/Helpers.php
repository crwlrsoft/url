<?php

namespace Crwlr\Url;

/**
 * Class Helpers
 *
 * This class provides instances of the Suffixes and Schemes classes via static methods and also some simple
 * static helper methods.
 *
 * Providing these class instances via static methods ensures better performance as they don't need to be newly
 * instantiated for anything.
 */

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
     * Get an instance of the Suffixes class.
     *
     * @return Suffixes
     */
    public static function suffixes(): Suffixes
    {
        if (!self::$suffixes instanceof Suffixes) {
            self::$suffixes = new Suffixes();
        }

        return self::$suffixes;
    }

    /**
     * Get an instance of the Schemes class.
     *
     * @return Schemes
     */
    public static function schemes(): Schemes
    {
        if (!self::$schemes instanceof Schemes) {
            self::$schemes = new Schemes();
        }

        return self::$schemes;
    }

    /**
     * Builds a url from an array of url components.
     *
     * It doesn't do any validation and assumes the provided component values are valid!
     *
     * @param array $comp
     * @return string
     */
    public static function buildUrlFromComponents(array $comp = []): string
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
    public static function queryStringToArray(string $query = ''): array
    {
        parse_str($query, $array);

        if (preg_match('/(?:^|&)([^\[=&]*\.)/', $query)) { // Matches keys in the query that contain a dot
            return self::replaceKeysContainingDots($query, $array);
        }

        return $array;
    }

    /**
     * Get the standard port for a url scheme.
     *
     * Uses PHP's built-in getservbyname() function. If no standard port is found it returns null.
     *
     * @param string $scheme
     * @return int|null
     */
    public static function getStandardPortByScheme(string $scheme): ?int
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
     * Strip some string B from the end of a string A that ends with string B.
     *
     * Example: 'some.example' - '.example' = 'some'
     *
     * @param string $string
     * @param string $strip
     * @return string
     */
    public static function stripFromEnd(string $string = '', string $strip = ''): string
    {
        $stripLength = strlen($strip);
        $stringLength = strlen($string);

        if ($stripLength > $stringLength) {
            return $string;
        }

        $endOfString = substr($string, ($stringLength - $stripLength));

        if ($endOfString === $strip) {
            return substr($string, 0, ($stringLength - $stripLength));
        }

        return $string;
    }

    /**
     * Strip some string B from the start of a string A that starts with string B.
     *
     * Example: 'some-example' - 'some-' = 'example'
     *
     * @param string $string
     * @param string $strip
     * @return string
     */
    public static function stripFromStart(string $string = '', string $strip = ''): string
    {
        if (strpos($string, $strip) === 0) {
            return substr($string, strlen($strip));
        }

        return $string;
    }

    /**
     * Replace the first occurrence of string A with string B in string C.
     *
     * Example: A: 'bar', B: 'boo', C: 'foo.bar.baz.bar' = 'foo.boo.baz.boo'
     *
     * @param string $replace
     * @param string $replacement
     * @param string $string
     * @return string
     */
    public static function replaceFirstOccurrence(string $replace, string $replacement, string $string): string
    {
        $positionInString = strpos($string, $replace);

        if ($positionInString === false) {
            return $string;
        }

        $preReplace = substr($string, 0, $positionInString);
        $postReplace = substr($string, ($positionInString + strlen($replace)));

        return $preReplace . $replacement . $postReplace;
    }

    /**
     * Returns true when string $string starts with string $startsWith.
     *
     * @param string $string
     * @param string $startsWith
     * @param int|null $length  When known, providing the length of the string $startsWith saves a call to strlen.
     * @return bool
     */
    public static function startsWith(string $string, string $startsWith, ?int $length = null): bool
    {
        return substr($string, 0, ($length !== null ? $length : strlen($startsWith))) === $startsWith;
    }

    /**
     * Returns true when $string contains $x before the first appearance of $y (even if $y is not contained at all).
     *
     * @param string $string
     * @param string $x
     * @param string $y
     * @return bool
     */
    public static function containsXBeforeFirstY(string $string, string $x, string $y): bool
    {
        $untilFirstY = explode($y, $string)[0];

        return strpos($untilFirstY, $x) !== false;
    }

    /**
     * Helper method for queryStringToArray
     *
     * When keys within a url query string contain dots, PHP's parse_str() method converts them to underscores. This
     * method works around this issue so the requested query array returns the proper keys with dots.
     *
     * @param string $query
     * @param array $array
     * @return array
     */
    private static function replaceKeysContainingDots(string $query, array $array): array
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
