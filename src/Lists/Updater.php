<?php

namespace Crwlr\Url\Lists;

use Crwlr\Url\Exceptions\ListUpdaterException;

/**
 * Class Updater
 *
 * Parent class for List Updaters that provides shared methods for fetching the original contents and storing them.
 * Parsing the original file to a list must be implemented in child classes.
 */

abstract class Updater
{
    /**
     * In a child class the url where the original list can be loaded from needs to be defined,
     * otherwise instantiating the child class will throw a ListUpdaterException.
     *
     * @var string
     */
    protected $url = '';

    /**
     * In a child class a filename for the original list file needs to be defined,
     * otherwise instantiating the child class will throw a ListUpdaterException.
     *
     * @var string
     */
    protected $originalFilename = '';

    /**
     *
     *
     * @var string
     */
    protected $originalPath = '';

    /**
     * @throws ListUpdaterException
     */
    public function __construct()
    {
        $this->checkUrl();
        $this->setOriginalPath();
    }

    /**
     * In a child class write a method that parses the contents of the original file and return
     * a simple array containing the list values.
     *
     * @param string $content
     * @return array
     */
    abstract protected function parseContent(string $content = '') : array;

    /**
     * A child class needs this function that returns the path where the parsed list will be stored.
     *
     * @return string
     */
    abstract protected function getListStorePath() : string;

    /**
     * Perform update.
     */
    public function update()
    {
        try {
            $content = $this->loadAndStoreOriginal();
        } catch (\Exception $exception) {
            $content = $this->getContentFromOldFile();
        }

        $parsed = $this->parseContent($content);
        $this->storeList($parsed);
    }

    /**
     * @throws ListUpdaterException
     */
    private function checkUrl()
    {
        if (!is_string($this->url) || trim($this->url) === '') {
            throw new ListUpdaterException('No url to load the original list from is defined.');
        }
    }

    /**
     * @throws ListUpdaterException
     */
    private function setOriginalPath()
    {
        if (!is_string($this->originalFilename) || trim($this->originalFilename) === '') {
            throw new ListUpdaterException(
                get_called_class() . ' has no filename where the original list content should be stored.'
            );
        }

        $this->originalPath = dirname(__DIR__) . '/../data/' . $this->originalFilename;
    }

    /**
     * @return string
     */
    private function loadAndStoreOriginal() : string
    {
        $content = file_get_contents($this->url);
        $content = str_replace("\r\n", "\n", $content); // Replace CRLF line breaks.
        file_put_contents($this->originalPath, $content);

        return $content;
    }

    /**
     * @return bool|string
     */
    private function getContentFromOldFile()
    {
        return file_get_contents($this->originalPath);
    }

    /**
     * @param array $parsed
     */
    private function storeList(array $parsed = [])
    {
        $storeContent = "<?php return [";

        foreach ($parsed as $listValue) {
            // Store the values as the array keys for faster search and as values just use 0 to save disk space.
            $storeContent .= "'" . $listValue . "'=>0,";
        }

        $storeContent .= "];\n";
        file_put_contents($this->getListStorePath(), $storeContent);
    }
}
