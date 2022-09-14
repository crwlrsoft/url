<?php

namespace Crwlr\Url\Lists;

use Crwlr\Url\Exceptions\ListUpdaterException;
use Exception;

/**
 * Class WebUpdater
 *
 * Extension of the Updater class for Updaters that load data from a source file from the web and parse them to a
 * simple list array.
 */

abstract class WebUpdater extends Updater
{
    /**
     * In a child class the URL where the original list can be loaded from needs to be defined,
     * otherwise instantiating the child class will throw a ListUpdaterException.
     *
     * @var string
     */
    protected string $url = '';

    /**
     * In a child class a filename for the original list file needs to be defined,
     * otherwise instantiating the child class will throw a ListUpdaterException.
     *
     * @var string
     */
    protected string $originalFilename = '';

    /**
     * Path where the original source file will be stored.
     *
     * @var string
     * @see Updater::setOriginalPath()
     */
    protected string $originalPath = '';

    /**
     * @throws ListUpdaterException
     */
    public function __construct()
    {
        $this->checkUrl();
        $this->setOriginalPath();
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getOriginalContent(): string
    {
        $context = stream_context_create(['http' =>
            [
                'method' => 'GET',
                'header'  => [
                    'User-Agent: https://github.com/crwlrsoft/url/blob/master/src/Lists/WebUpdater.php',
                ]
            ]
        ]);
        $content = file_get_contents($this->url, false, $context);

        if (!$content) {
            throw new ListUpdaterException("Fetching list file failed.");
        }

        $content = str_replace("\r\n", "\n", $content); // Replace CRLF line breaks.
        file_put_contents($this->originalPath, $content);

        return $content;
    }

    /**
     * @param array<string|int> $parsed
     */
    protected function storeList(array $parsed = []): void
    {
        $storeContent = "<?php return [";

        foreach ($parsed as $listValue) {
            // Store the values as the array keys for faster search and as values just use 0 to save disk space.
            $storeContent .= "'" . $listValue . "'=>0,";
        }

        $storeContent .= "];\n";
        file_put_contents($this->getListStorePath(), $storeContent);
    }

    /**
     * @throws ListUpdaterException
     */
    private function checkUrl(): void
    {
        if (trim($this->url) === '') {
            throw new ListUpdaterException('No URL to load the original list from is defined.');
        }
    }

    /**
     * @throws ListUpdaterException
     */
    private function setOriginalPath(): void
    {
        if (trim($this->originalFilename) === '') {
            throw new ListUpdaterException(
                get_called_class() . ' has no filename where the original list content should be stored.'
            );
        }

        $this->originalPath = dirname(__DIR__) . '/../data/' . $this->originalFilename;
    }
}
