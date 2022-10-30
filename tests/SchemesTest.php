<?php

use Crwlr\Url\Schemes;

test('Exists', function () {
    $schemes = new Schemes();

    $this->assertTrue($schemes->exists('http'));
    $this->assertTrue($schemes->exists('https'));
    $this->assertTrue($schemes->exists('ftp'));
    $this->assertTrue($schemes->exists('git'));
    $this->assertTrue($schemes->exists('mailto'));
    $this->assertTrue($schemes->exists('sftp'));
    $this->assertTrue($schemes->exists('wss'));

    $this->assertFalse($schemes->exists('doesnt-exist'));
});

test('GetStorePath', function () {
    $schemes = new Schemes();
    $this->assertEquals(realpath(dirname(__DIR__) . '/data/schemes.php'), $schemes->getStorePath());
});
