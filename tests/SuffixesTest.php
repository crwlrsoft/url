<?php
declare(strict_types=1);

use Crwlr\Url\Suffixes;
use PHPUnit\Framework\TestCase;

final class SuffixesTest extends TestCase
{
    public function testExists()
    {
        $suffixes = new Suffixes();

        $this->assertTrue($suffixes->exists('com'));
        $this->assertTrue($suffixes->exists('org'));
        $this->assertTrue($suffixes->exists('com.nr'));
        $this->assertTrue($suffixes->exists('edu.mx'));

        $this->assertFalse($suffixes->exists('does.not.exist'));
    }

    public function testGetStorePath()
    {
        $schemes = new Suffixes();
        $this->assertEquals(realpath(dirname(__DIR__) . '/data/suffixes.php'), $schemes->getStorePath());
    }
}
