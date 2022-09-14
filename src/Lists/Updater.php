<?php

namespace Crwlr\Url\Lists;

/**
 * Class Updater
 *
 * Abstract parent class for List Updaters that get data from some source and store them in a simple PHP file within
 * this package.
 */

abstract class Updater
{
    /**
     * For simple lists of values, lists are stored with the value as the key and 0 as value for faster search.
     * When key => value is needed, set this to false.
     *
     * @var bool
     */
    protected bool $storeValuesAsKeys = true;

    /**
     * The default procedure for an Updater: get source contents, parse and store them.
     */
    public function update(): void
    {
        $content = $this->getOriginalContent();

        $parsed = $this->parseContent($content);

        $this->storeList($parsed);
    }

    /**
     * In a child class implement a method that returns the original/source content (may be string, array or whatever).
     *
     * @return string|array|mixed
     */
    abstract protected function getOriginalContent();

    /**
     * In a child class implement a method that parses the contents to an array representing the list to store.
     *
     * @param string|array|mixed $content
     * @return array|(string|int)[]
     */
    abstract protected function parseContent($content = ''): array;

    /**
     * A child class needs this function that returns the path where the parsed list will be stored.
     *
     * @return string
     */
    abstract protected function getListStorePath(): string;

    /**
     * @param array|(string|int)[] $parsed
     */
    protected function storeList(array $parsed = []): void
    {
        $content = "<?php return [";

        foreach ($parsed as $key => $value) {
            $content .= $this->storeValuesAsKeys ? "'" . $value . "'=>0," : "'" . $key . "'=>" . $value . ",";
        }

        $content .= "];\n";
        file_put_contents($this->getListStorePath(), $content);
    }
}
