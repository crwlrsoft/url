<?php

use Crwlr\Url\DefaultPorts;

/** @var \PHPUnit\Framework\TestCase $this */

test('GetFallbackDefaultPorts', function () {
    $this->assertEquals(21, (new DefaultPorts())->get('ftp'));
    $this->assertEquals(9418, (new DefaultPorts())->get('git'));
    $this->assertEquals(80, (new DefaultPorts())->get('http'));
    $this->assertEquals(443, (new DefaultPorts())->get('https'));
    $this->assertEquals(143, (new DefaultPorts())->get('imap'));
    $this->assertEquals(194, (new DefaultPorts())->get('irc'));
    $this->assertEquals(994, (new DefaultPorts())->get('ircs'));
    $this->assertEquals(389, (new DefaultPorts())->get('ldap'));
    $this->assertEquals(636, (new DefaultPorts())->get('ldaps'));
    $this->assertEquals(2049, (new DefaultPorts())->get('nfs'));
    $this->assertEquals(115, (new DefaultPorts())->get('sftp'));
    $this->assertEquals(25, (new DefaultPorts())->get('smtp'));
    $this->assertEquals(22, (new DefaultPorts())->get('ssh'));
});

test('GetDefaultPortsNotInFallbackList', function () {
    $this->assertEquals(2019, (new DefaultPorts())->get('about'));
    $this->assertEquals(674, (new DefaultPorts())->get('acap'));
    $this->assertEquals(70, (new DefaultPorts())->get('gopher'));
    $this->assertEquals(1038, (new DefaultPorts())->get('mtqp'));
    $this->assertEquals(2009, (new DefaultPorts())->get('news'));
    $this->assertEquals(873, (new DefaultPorts())->get('rsync'));
    $this->assertEquals(3690, (new DefaultPorts())->get('svn'));
    $this->assertEquals(23, (new DefaultPorts())->get('telnet'));
    $this->assertEquals(516, (new DefaultPorts())->get('videotex'));
});

test('Exists', function () {
    $this->assertTrue((new DefaultPorts())->exists('http'));
    $this->assertFalse((new DefaultPorts())->exists('notexistingscheme'));
});

test('GetStorePath', function () {
    $defaultPorts = new DefaultPorts();
    $this->assertEquals(realpath(dirname(__DIR__) . '/data/default-ports.php'), $defaultPorts->getStorePath());
});
