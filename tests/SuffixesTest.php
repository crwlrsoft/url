<?php

use Crwlr\Url\Suffixes;

test('Exists', function () {
    $suffixes = new Suffixes();

    $this->assertTrue($suffixes->exists('com'));
    $this->assertTrue($suffixes->exists('org'));
    $this->assertTrue($suffixes->exists('com.nr'));
    $this->assertTrue($suffixes->exists('edu.mx'));

    $this->assertFalse($suffixes->exists('does.not.exist'));
});

test('GetStorePath', function () {
    $schemes = new Suffixes();
    $this->assertEquals(realpath(dirname(__DIR__) . '/data/suffixes.php'), $schemes->getStorePath());
});
