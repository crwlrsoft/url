<?php

namespace Crwlr\Url;

/**
 * Class Domain
 *
 * Is created from a registrable domain string, parses it to suffix and label if it ends with a public suffix.
 */

class Domain
{
    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string|null
     */
    private $suffix;

    /**
     * Checks if a public suffix is present in the $domain and splits the domain into label and suffix if yes.
     *
     * @param string $domain
     * @param string|null $suffix
     */
    public function __construct(string $domain, string $suffix = null)
    {
        if (!$suffix) {
            $suffix = Helpers::suffixes()->getByHost($domain);
        }

        if ($suffix) {
            $this->suffix = $suffix;
            $withoutDomainSuffix = Helpers::stripFromEnd($domain, '.' . $suffix);
            $splitByDot = explode('.', $withoutDomainSuffix);
            $this->label = end($splitByDot);
        }
    }

    /**
     * Return the current domain instance as a string when both, label and suffix, are not empty.
     *
     * @return string
     */
    public function __toString(): string
    {
        if (empty($this->label) || empty($this->suffix)) {
            return '';
        }

        return $this->label . '.' . $this->suffix;
    }

    /**
     * (Set and/or) Get the domain label
     *
     * @param string|null $newLabel
     * @return string|null
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
     *
     * @param string|null $newSuffix
     * @return string|null
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
     *
     * @return bool
     */
    public function isIdn(): bool
    {
        $domain = $this->__toString();

        return Helpers::idn_to_utf8($domain) !== $domain;
    }
}
