<?php

namespace Crwlr\Url\Schemes;

use Crwlr\Url\Schemes;

/**
 * Class Updater
 *
 * Load, parse and store the list of all existing URI Schemes from iana.org
 * https://www.iana.org/assignments/uri-schemes/uri-schemes.xhtml
 */

class Updater extends \Crwlr\Url\Lists\Updater
{
    /**
     * @var string
     */
    protected $url = 'https://www.iana.org/assignments/uri-schemes/uri-schemes-1.csv';

    /**
     * @var string
     */
    protected $originalFilename = 'uri-schemes.csv';

    /**
     * @param string $content
     * @return array
     */
    protected function parseContent(string $content = ''): array
    {
        $schemes = [];

        foreach (explode("\n", $content) as $lineNumber => $line) {
            if ($lineNumber === 0 || substr($line, 0, 1) === ' ') {
                continue;
            }

            $parsedLine = str_getcsv($line, ',', '', '');

            if (count($parsedLine) > 1) {
                $schemes[] = $parsedLine[0];
            }
        }

        return $schemes;
    }

    /**
     * @return string
     */
    protected function getListStorePath(): string
    {
        return (new Schemes())->getStorePath();
    }
}
