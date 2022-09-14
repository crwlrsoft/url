<?php

namespace Crwlr\Url;

/**
 * Class Domain
 *
 * Is created from a registrable domain string, parses it to suffix and label if it ends with a public suffix.
 */

class Domain
{
    private ?string $label = null;

    private ?string $suffix = null;

    /**
     * Checks if a public suffix is present in the $domain and splits the domain into label and suffix if yes.
     *
     * @param string $domain
     * @param string|null $suffix
     */
    public function __construct(string $domain, ?string $suffix = null)
    {
        if (!$suffix) {
            $suffix = Helpers::suffixes()->getByHost($domain);
        }

        if ($suffix) {
            $this->suffix = $suffix;
            $withoutDomainSuffix = Helpers::stripFromEnd($domain, '.' . $suffix);
            $splitAtDot = explode('.', $withoutDomainSuffix);
            $this->label = end($splitAtDot);
        }
    }

    /**
     * Return the current domain instance as a string when both, label and suffix, are not empty.
     */
    public function __toString(): string
    {
        return !empty($this->label) && !empty($this->suffix) ? $this->label . '.' . $this->suffix : '';
    }

    /**
     * (Set and/or) Get the domain label
     */
    public function label(?string $newLabel = null): ?string
    {
        if ($newLabel !== null) {
            $this->label = !empty($newLabel) ? $newLabel : null;
        }

        return $this->label;
    }

    /**
     * (Set and/or) Get the domain suffix
     */
    public function suffix(?string $newSuffix = null): ?string
    {
        if ($newSuffix !== null) {
            $this->suffix = !empty($newSuffix) ? $newSuffix : null;
        }

        return $this->suffix;
    }

    /**
     * Return true when the current domain is an internationalized domain name.
     */
    public function isIdn(): bool
    {
        $domain = $this->__toString();

        return Helpers::idn_to_utf8($domain) !== $domain;
    }
}
