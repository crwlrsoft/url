<?php

namespace Crwlr\Url;

/**
 * Class Host
 *
 * Is created from a host string and makes the possible components subdomain and domain (label and suffix)
 * accessible separately.
 */

class Host
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string|null
     */
    private $subdomain;

    /**
     * @var Domain
     */
    private $domain;

    /**
     * Host constructor.
     *
     * If you use this class directly, please validate the $host string first with Validator->host().
     *
     * @param string $host
     */
    public function __construct(string $host)
    {
        $this->host = Helpers::encodeHost($host);
        $domainSuffix = Helpers::suffixes()->getByHost($this->host);

        if ($domainSuffix) {
            $this->domain = new Domain($this->host, $domainSuffix);
            $this->subdomain = Helpers::stripFromEnd($this->host, '.' . $this->domain);
        }
    }

    public function __toString() : string
    {
        return $this->host;
    }

    /**
     * Get or set the registrable domain.
     *
     * @param string|null $domain
     * @return null|string
     */
    public function domain(string $domain = null)
    {
        if ($domain === null) {
            return $this->domainNotEmpty() ? $this->domain->__toString() : null;
        }

        $this->domain = new Domain($domain);
        $this->updateHost();
    }

    /**
     * Get or set the subdomain part of the host.
     *
     * @param string|null $subdomain
     * @return null|string
     */
    public function subdomain(string $subdomain = null)
    {
        if ($subdomain === null) {
            return !empty($this->subdomain) ? $this->subdomain : null;
        }

        $this->subdomain = $subdomain;
        $this->updateHost();
    }

    /**
     * Get or set the domain label (registrable domain without suffix).
     *
     * @param string|null $domainLabel
     * @return mixed|null
     */
    public function domainLabel(string $domainLabel = null)
    {
        if ($this->domain instanceof Domain) {
            if ($domainLabel === null) {
                return $this->domain->label();
            }

            $this->domain->label($domainLabel);
            $this->updateHost();
        } elseif ($domainLabel === null) {
            return null;
        }
    }

    /**
     * Get or set the public suffix of the registrable domain.
     *
     * @param string|null $domainSuffix
     * @return null|string
     */
    public function domainSuffix(string $domainSuffix = null)
    {
        if ($this->domain instanceof Domain) {
            if ($domainSuffix === null) {
                return $this->domain->suffix();
            }

            $this->domain->suffix($domainSuffix);
            $this->updateHost();
        } elseif ($domainSuffix === null) {
            return null;
        }
    }

    /**
     * Update the full host string.
     */
    private function updateHost()
    {
        if ($this->domainNotEmpty()) {
            $this->host =  ($this->subdomain ? $this->subdomain . '.' : '') . $this->domain->__toString();
        } else {
            $this->host = '';
        }
    }

    /**
     * True when this class has an instance of domain that is not empty.
     *
     * @return bool
     */
    private function domainNotEmpty() : bool
    {
        if ($this->domain instanceof Domain && !empty($this->domain->__toString())) {
            return true;
        }

        return false;
    }
}
