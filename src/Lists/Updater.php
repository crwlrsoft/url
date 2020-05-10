<?php

namespace Crwlr\Url\Lists;

use Crwlr\Url\Exceptions\ListUpdaterException;
use Exception;

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
     * Path where the original source file will be stored.
     *
     * @var string
     * @see Updater::setOriginalPath()
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
     * @throws Exception
     */
    public function update(): void
    {
        $content = $this->loadAndStoreOriginal();
        $parsed = $this->parseContent($content);
        $this->storeList($parsed);
    }

    /**
     * In a child class write a method that parses the contents of the original file and return
     * a simple array containing the list values.
     *
     * @param string $content
     * @return array
     */
    abstract protected function parseContent(string $content = ''): array;

    /**
     * A child class needs this function that returns the path where the parsed list will be stored.
     *
     * @return string
     */
    abstract protected function getListStorePath(): string;

    /**
     * @throws ListUpdaterException
     */
    private function checkUrl(): void
    {
        if (!is_string($this->url) || trim($this->url) === '') {
            throw new ListUpdaterException('No url to load the original list from is defined.');
        }
    }

    /**
     * @throws ListUpdaterException
     */
    private function setOriginalPath(): void
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
     * @throws Exception
     */
    private function loadAndStoreOriginal(): string
    {
        $context = stream_context_create(['http' =>
            [
                'method' => 'GET',
                'header'  => [
                    'User-Agent: https://github.com/crwlrsoft/url/blob/master/src/Schemes/Updater.php',
                ]
            ]
        ]);
        $content = file_get_contents($this->url, false, $context);

        if (!$content) {
            throw new Exception("Fetching list file failed.");
        }

        $content = str_replace("\r\n", "\n", $content); // Replace CRLF line breaks.
        file_put_contents($this->originalPath, $content);

        return $content;
    }

    /**
     * @param array $parsed
     */
    private function storeList(array $parsed = []): void
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
