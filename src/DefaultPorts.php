<?php

namespace Crwlr\Url;

use Crwlr\Url\Lists\Store;

/**
 * Class DefaultPorts
 *
 * List of default ports for URI schemes.
 */

class DefaultPorts extends Store
{
    protected $storeFilename = 'default-ports.php';

    /**
     * @var int[]
     */
    protected $fallbackList = [
        'ftp' => 21,
        'git' => 9418,
        'http' => 80,
        'https' => 443,
        'imap' => 143,
        'irc' => 194,
        'ircs' => 994,
        'ldap' => 389,
        'ldaps' => 636,
        'nfs' => 2049,
        'sftp' => 115,
        'smtp' => 25,
        'ssh' => 22,
    ];
}
