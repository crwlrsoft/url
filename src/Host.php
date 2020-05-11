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
     * If you use this class directly, please validate the $host string first with Validator->host().
     *
     * @param string $host
     */
    public function __construct(string $host)
    {
        $this->host = $host;
        $domainSuffix = Helpers::suffixes()->getByHost($this->host);

        if ($domainSuffix) {
            $this->domain = new Domain($this->host, $domainSuffix);
            $this->subdomain = Helpers::stripFromEnd($this->host, '.' . $this->domain);
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->host;
    }

    /**
     * (Set and/or) Get the registrable domain.
     *
     * @param string|null $domain
     * @return string|null
     */
    public function domain(?string $domain = null): ?string
    {
        if ($domain !== null) {
            $this->domain = new Domain($domain);
            $this->updateHost();
        }

        return $this->domainNotEmpty() ? $this->domain->__toString() : null;
    }

    /**
     * (Set and/or) Get the subdomain part of the host.
     *
     * @param string|null $subdomain
     * @return string|null
     */
    public function subdomain(?string $subdomain = null): ?string
    {
        if ($subdomain !== null) {
            $this->subdomain = $subdomain;
            $this->updateHost();
        }

        return !empty($this->subdomain) ? $this->subdomain : null;
    }

    /**
     * (Set and/or) Get the domain label (registrable domain without suffix).
     *
     * @param string|null $domainLabel
     * @return string|null
     */
    public function domainLabel(?string $domainLabel = null): ?string
    {
        if ($domainLabel !== null && $this->domain instanceof Domain) {
            $this->domain->label($domainLabel);
            $this->updateHost();
        }

        return $this->domain instanceof Domain ? $this->domain->label() : null;
    }

    /**
     * (Set and/or) Get the public suffix of the registrable domain.
     *
     * @param string|null $domainSuffix
     * @return string|null
     */
    public function domainSuffix(?string $domainSuffix = null): ?string
    {
        if ($domainSuffix !== null && $this->domain instanceof Domain) {
            $this->domain->suffix($domainSuffix);
            $this->updateHost();
        }

        return $this->domain instanceof Domain ? $this->domain->suffix() : null;
    }

    /**
     * Returns true when the host contains an internationalized domain name.
     *
     * @return bool
     */
    public function hasIdn(): bool
    {
        return $this->domain instanceof Domain ? $this->domain->isIdn() : false;
    }

    /**
     * Update the full host string.
     */
    private function updateHost(): void
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
    private function domainNotEmpty(): bool
    {
        if ($this->domain instanceof Domain && !empty($this->domain->__toString())) {
            return true;
        }

        return false;
    }
}
