<?php

namespace Crwlr\Url;

use Crwlr\Url\Lists\Store;

/**
 * Class Schemes
 *
 * This class gives access to the list of all URI schemes from iana.org.
 * https://www.iana.org/assignments/uri-schemes/uri-schemes.xhtml
 */

class Schemes extends Store
{
    /**
     * @var string
     */
    protected $storeFilename = 'schemes.php';

    /**
     * Fallback list if list file loading fails.
     *
     * @var int[]
     */
    protected $fallbackList = [
        'cvs' => 0,
        'data' => 0,
        'dns' => 0,
        'facetime' => 0,
        'feed' => 0,
        'file' => 0,
        'ftp' => 0,
        'geo' => 0,
        'git' => 0,
        'http' => 0,
        'https' => 0,
        'imap' => 0,
        'irc' => 0,
        'mailto' => 0,
        'nfs' => 0,
        'redis' => 0,
        'sftp' => 0,
        'skype' => 0,
        'smb' => 0,
        'smtp' => 0,
        'ssh' => 0,
        'svn' => 0,
        'telnet' => 0,
        'view-source' => 0,
        'ws' => 0,
        'wss' => 0,
    ];
}
