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
    private static ?Suffixes $suffixes = null;

    private static ?Schemes $schemes = null;

    private static ?DefaultPorts $defaultPorts = null;

    /**
     * Get an instance of the Suffixes class.
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
     */
    public static function schemes(): Schemes
    {
        if (!self::$schemes instanceof Schemes) {
            self::$schemes = new Schemes();
        }

        return self::$schemes;
    }

    /**
     * Get an instance of the DefaultPorts class.
     */
    public static function defaultPorts(): DefaultPorts
    {
        if (!self::$defaultPorts instanceof DefaultPorts) {
            self::$defaultPorts = new DefaultPorts();
        }

        return self::$defaultPorts;
    }

    /**
     * Builds a URL from an array of URL components.
     *
     * It doesn't do any validation and assumes the provided component values are valid!
     *
     * @param array<int|string> $components
     */
    public static function buildUrlFromComponents(array $components): string
    {
        $url = '';

        if (isset($components['scheme'])) {
            $url .= $components['scheme'] . ':';

            if (
                isset($components['port']) &&
                $components['port'] === self::getStandardPortByScheme($components['scheme'])
            ) {
                unset($components['port']);
            }
        }

        $url .= isset($components['host']) ? '//' : '';
        $url .= self::buildAuthorityFromComponents($components);
        $url .= $components['path'] ?? '';
        $url .= isset($components['query']) ? '?' . $components['query'] : '';
        $url .= isset($components['fragment']) ? '#' . $components['fragment'] : '';

        return $url;
    }

    /**
     * Builds an authority string from an array of components (host, user, password, port)
     *
     * It doesn't do any validation and assumes the provided component values are valid!
     *
     * @param array<int|string> $components
     */
    public static function buildAuthorityFromComponents(array $components): string
    {
        $authority = '';

        if (isset($components['host']) && $components['host']) {
            $authority .= self::buildUserInfoFromComponents($components);

            if ($authority !== '') {
                $authority .= '@';
            }

            $authority .= $components['host'];

            if (isset($components['port']) && $components['port']) {
                $authority .= ':' . $components['port'];
            }
        }

        return $authority;
    }

    /**
     * Builds a user info string from components (user, password)
     *
     * It doesn't do any validation and assumes the provided component values are valid!
     *
     * @param array<int|string> $components
     */
    public static function buildUserInfoFromComponents(array $components): string
    {
        $userInfo = '';

        if (isset($components['user']) && $components['user']) {
            $userInfo = $components['user'];

            if (isset($components['password']) && $components['password']) {
                $userInfo .= ':' . $components['password'];
            } elseif (isset($components['pass']) && $components['pass']) {
                $userInfo .= ':' . $components['pass'];
            }
        }

        return $userInfo;
    }

    /**
     * Converts a URL query string to array.
     *
     * @return string[]
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
     * Get the standard port for a URL scheme.
     *
     * Uses the DefaultPorts list class or tries as fallback PHPs built-in getservbyname() function that get's
     * default ports from the /etc/services file. If no standard port is found it returns null.
     */
    public static function getStandardPortByScheme(string $scheme): ?int
    {
        $scheme = strtolower(trim($scheme));

        if ($scheme === '') {
            return null;
        }

        $defaultPort = self::defaultPorts()->get($scheme);

        if ($defaultPort) {
            return $defaultPort;
        }

        $standardPortTcp = getservbyname($scheme, 'tcp');

        if ($standardPortTcp) {
            return $standardPortTcp;
        }

        $standardPortUdp = getservbyname($scheme, 'udp');

        if ($standardPortUdp) {
            return $standardPortUdp;
        }

        return null;
    }

    /**
     * Strip some string B from the end of a string A that ends with string B.
     *
     * Example: 'some.example' - '.example' = 'some'
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
     */
    public static function stripFromStart(string $string = '', string $strip = ''): string
    {
        if (str_starts_with($string, $strip)) {
            return substr($string, strlen($strip));
        }

        return $string;
    }

    /**
     * Replace the first occurrence of string A with string B in string C.
     *
     * Example: A: 'bar', B: 'boo', C: 'foo.bar.baz.bar' = 'foo.boo.baz.boo'
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
     * @param int|null $length  When known, providing the length of the string $startsWith saves a call to strlen.
     */
    public static function startsWith(string $string, string $startsWith, ?int $length = null): bool
    {
        return substr($string, 0, ($length !== null ? $length : strlen($startsWith))) === $startsWith;
    }

    /**
     * Returns true when $string contains $x before the first appearance of $y (even if $y is not contained at all).
     */
    public static function containsXBeforeFirstY(string $string, string $x, string $y): bool
    {
        if ($y === '') {
            return str_contains($string, $x);
        }

        $untilFirstY = explode($y, $string)[0];

        return str_contains($untilFirstY, $x);
    }

    /**
     * IDN to ASCII
     *
     * Wrapper method for idn_to_ascii because from PHP 7.2 on variant INTL_IDNA_VARIANT_2003 is deprecated,
     * but still the default value for argument variant (PHP 7.4 uses INTL_IDNA_VARIANT_UTS46 as default).
     */
    public static function idn_to_ascii(string $string): string
    {
        $converted = idn_to_ascii($string, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);

        return $converted !== false ? $converted : $string;
    }

    /**
     * IDN to UTF-8
     *
     * Wrapper method for idn_to_utf8 because from PHP 7.2 on variant INTL_IDNA_VARIANT_2003 is deprecated,
     * but still the default value for argument variant (PHP 7.4 uses INTL_IDNA_VARIANT_UTS46 as default).
     */
    public static function idn_to_utf8(string $string): string
    {
        $converted = idn_to_utf8($string, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);

        return $converted !== false ? $converted : $string;
    }

    /**
     * Helper method for queryStringToArray
     *
     * When keys within a URL query string contain dots, PHP's parse_str() method converts them to underscores. This
     * method works around this issue so the requested query array returns the proper keys with dots.
     *
     * @param string[] $array
     * @return string[]
     */
    private static function replaceKeysContainingDots(string $query, array $array): array
    {
        // Regex to find keys in query string.
        preg_match_all('/(?:^|&)([^=&\[]+)(?:[=&\[]|$)/', $query, $matches);
        $brokenKeys = $fixedArray = [];

        // Create mapping of broken keys to original proper keys.
        foreach ($matches[1] as $value) {
            if (str_contains($value, '.')) {
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
