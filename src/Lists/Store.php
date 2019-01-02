<?php

namespace Crwlr\Url\Lists;

/**
 * Class Store
 *
 * The Url Package uses lists like Mozilla's Public Suffix List that contains all public domain suffixes.
 * These lists are loaded from an external source, parsed and stored in a php file in the data directory at
 * the root level.
 *
 * This is a parent class for the objects that are used to look-up these parsed lists. It provides functionality
 * for loading the list from it's file in the data directory and a basic exists() method.
 *
 * You can define a fallback list in the child class to ensure basic look-ups will work even if loading of the
 * list in the data directory fails. It can also be useful for performance if the full list is very big.
 * The exists method in this class will first check the fallback list before loading the full list.
 */

abstract class Store
{
    /**
     * The list as an array, with the values as the keys for fast search
     * (in_array would be slow with a large number of values).
     *
     * @var array
     */
    protected $list;

    /**
     * Fallback list if list file loading fails.
     *
     * @var array
     */
    protected $fallbackList = [];

    /**
     * In a child class the filename where the list is stored needs to be declared,
     * otherwise instantiating the child class will throw a ListStoreException.
     *
     * @var string
     */
    protected $storeFilename = '';

    /**
     * The full store path is generated in the setStorePath() method, no need to declare it manually.
     *
     * @var string
     */
    protected $storePath = '';

    public function __construct()
    {
        $this->setStorePath();
    }

    /**
     * Returns true if $key is contained in the list.
     *
     * @param mixed $key
     * @return bool
     */
    public function exists($key) : bool
    {
        if (isset($this->fallbackList[$key])) {
            return true;
        }

        $this->loadFullList();

        if (isset($this->list[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Generates the full store path of the file where the list is stored in the data directory at the root level.
     * If the child class does not declare a store filename the list will be empty.
     */
    private function setStorePath()
    {
        if (is_string($this->storeFilename) && trim($this->storeFilename) !== '') {
            $this->storePath = dirname(__DIR__) . '/../data/' . $this->storeFilename;
        }
    }

    /**
     * Returns the full path where the parsed list is stored.
     *
     * @return string
     */
    public function getStorePath() : string
    {
        return $this->storePath;
    }

    /**
     * The full list isn't automatically loaded in the constructor for performance reasons.
     * A child class of the Store may be instantiated but never queried, so only load it when necessary.
     * If the exists() method is replaced in a child class you need to call this method.
     */
    protected function loadFullList()
    {
        if (!is_array($this->list) && !empty($this->storePath)) {
            $this->list = include($this->storePath);
        } elseif (!is_array($this->list)) {
            $this->list = [];
        }
    }
}
