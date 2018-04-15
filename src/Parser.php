<?php

namespace Crwlr\Url;

/**
 * Class Parser
 *
 * This class is responsible for parsing urls or parts of urls. The main parse method uses PHPs built-in
 * parse_url but there are also the methods that handle parsing the host component of a url to domain, suffix and
 * subdomain. It therefore utilizes the Suffixes class which knows all the public domain suffixes.
 */

class Parser
{
    /**
     * @var Suffixes
     */
    private $suffixes;

    /**
     * @param Suffixes|null $suffixes
     */
    public function __construct(Suffixes $suffixes = null)
    {
        $this->suffixes = ($suffixes instanceof Suffixes) ? $suffixes : new Suffixes();
    }

    /**
     * @param string $url
     * @param bool $parseDomain  If set to true it will also return domain, suffix and subdomain separately.
     * @return array
     */
    public function parse(string $url = '', bool $parseDomain = false) : array
    {
        $parsedUrl = parse_url($url);
        $parsedUrl['url'] = $url;

        if ($parseDomain === true && isset($parsedUrl['host']) && !empty($parsedUrl['host'])) {
            $host = $parsedUrl['host'];
            $parsedUrl['domainSuffix'] = $this->getDomainSuffixFromHost($host);
            $parsedUrl['domain'] = $this->getDomainFromHost($host, $parsedUrl['domainSuffix']);
            $parsedUrl['subdomain'] = $this->getSubdomainFromHost($host, $parsedUrl['domain']);
        }

        return $parsedUrl;
    }

    /**
     * Extract the registrable domain part from a host (without the subdomain part).
     *
     * @param string $host
     * @param string|null $domainSuffix If the domain suffix contained in the host was already extracted
     *                                  before this method call. This is not validated here any further.
     * @return string|null
     */
    public function getDomainFromHost(string $host = '', string $domainSuffix = null)
    {
        if (!is_string($host) || strpos($host, '.') === false) {
            return null;
        }

        if (!$domainSuffix) {
            $domainSuffix = $this->suffixes->getByHost($host);

            if (!$domainSuffix) {
                return null;
            }
        }

        $hostWithoutDomainSuffix = self::stripFromEnd($host, '.' . $domainSuffix);
        $splitAtDot = explode('.', $hostWithoutDomainSuffix);

        return end($splitAtDot) . '.' . $domainSuffix;
    }

    /**
     * @param string $host
     * @return null|string
     */
    public function getDomainSuffixFromHost(string $host = '')
    {
        return $this->suffixes->getByHost($host);
    }

    /**
     * Returns only the subdomain part of a host.
     * e.g.:
     * www.example.com => www
     * sub.domain.example.com => sub.domain
     *
     * @param string $host
     * @param null|string $domain  Optional, if you have already extracted the registrable domain from the $host.
     * @return null|string
     */
    public function getSubdomainFromHost(string $host = '', string $domain = null)
    {
        if (!is_string($domain) || trim($domain) === '') {
            $domain = $this->getDomainFromHost($host);
        }

        if (!$domain || $host === $domain) {
            return null;
        }

        return self::stripFromEnd($host, '.' . $domain);
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
}
