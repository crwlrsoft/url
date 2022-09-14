<?php
declare(strict_types=1);

namespace Tests;

use Crwlr\Url\Schemes;
use PHPUnit\Framework\TestCase;

final class SchemesTest extends TestCase
{
    public function testExists(): void
    {
        $schemes = new Schemes();

        $this->assertTrue($schemes->exists('http'));
        $this->assertTrue($schemes->exists('https'));
        $this->assertTrue($schemes->exists('ftp'));
        $this->assertTrue($schemes->exists('git'));
        $this->assertTrue($schemes->exists('mailto'));
        $this->assertTrue($schemes->exists('sftp'));
        $this->assertTrue($schemes->exists('wss'));

        $this->assertFalse($schemes->exists('doesnt-exist'));
    }

    public function testGetStorePath(): void
    {
        $schemes = new Schemes();
        $this->assertEquals(realpath(dirname(__DIR__) . '/data/schemes.php'), $schemes->getStorePath());
    }
}
