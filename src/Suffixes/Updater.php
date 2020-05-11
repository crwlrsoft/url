<?php

namespace Crwlr\Url\Suffixes;

use Crwlr\Url\Suffixes;

/**
 * Class Updater
 *
 * Load, parse and store the Mozilla Publix Suffix List https://publicsuffix.org/list/
 */

class Updater extends \Crwlr\Url\Lists\Updater
{
    /**
     * @var string
     */
    protected $url = 'https://publicsuffix.org/list/public_suffix_list.dat';

    /**
     * @var string
     */
    protected $originalFilename = 'public_suffix_list.dat';

    /**
     * @param string $content
     * @return array
     */
    protected function parseContent(string $content = ''): array
    {
        $suffixes = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || substr(trim($line), 0, 2) === '//') {
                continue;
            }

            $suffixes[] = $line;
        }

        return $suffixes;
    }

    /**
     * @return string
     */
    protected function getListStorePath(): string
    {
        return (new Suffixes())->getStorePath();
    }
}
