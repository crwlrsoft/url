<?php

namespace Crwlr\Url\DefaultPorts;

use Crwlr\Url\DefaultPorts;
use Crwlr\Url\Helpers;

/**
 * Class DefaultPort Updater
 *
 * Get all available default ports for the full list of schemes using PHP's built-in getservbyname function.
 * The getservbyname function only works when a /etc/services file exists. If that file is missing
 * Helpers::getStandardPortByScheme() will return the current values from the data/default-ports.php file.
 */

class Updater extends \Crwlr\Url\Lists\Updater
{
    protected $storeValuesAsKeys = false;

    /**
     * @return int[]
     */
    protected function getOriginalContent(): array
    {
        return include(dirname(__DIR__) . '/../data/schemes.php');
    }

    /**
     * @param int[] $content
     * @return int[]
     */
    protected function parseContent($content = []): array
    {
        $defaultPorts = [];

        foreach ($content as $scheme => $zero) {
            $defaultPort = Helpers::getStandardPortByScheme($scheme);

            if ($defaultPort) {
                $defaultPorts[$scheme] = $defaultPort;
            }
        }

        return $defaultPorts;
    }

    /**
     * @return string
     */
    protected function getListStorePath(): string
    {
        return (new DefaultPorts())->getStorePath();
    }
}
