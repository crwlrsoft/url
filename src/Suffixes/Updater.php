<?php

namespace Crwlr\Url\Suffixes;

use Crwlr\Url\Lists\WebUpdater;
use Crwlr\Url\Suffixes;

/**
 * Class Updater
 *
 * Load, parse and store the Mozilla Publix Suffix List https://publicsuffix.org/list/
 */

class Updater extends WebUpdater
{
    protected string $url = 'https://publicsuffix.org/list/public_suffix_list.dat';

    protected string $originalFilename = 'public_suffix_list.dat';

    /**
     * @param string $content
     * @return array|string[]
     */
    protected function parseContent($content = ''): array
    {
        $suffixes = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '//')) {
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
