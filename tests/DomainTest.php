<?php

use Crwlr\Url\Domain;

/** @var \PHPUnit\Framework\TestCase $this */

/**
 * The domain class is really simple and assumes input validation happens somewhere else, so this test
 * is rather short ;p
 */
test('Domain', function () {
    $domain = new Domain('example.com');
    $this->assertInstanceOf(Domain::class, $domain);
    $this->assertEquals('example', $domain->label());
    $this->assertEquals('com', $domain->suffix());
    $this->assertEquals('example.com', $domain->__toString());

    $domain = new Domain('notadomain');
    $this->assertInstanceOf(Domain::class, $domain);
    $this->assertNull($domain->label());
    $this->assertNull($domain->suffix());
    $this->assertEmpty($domain->__toString());
});

test('IsIdn', function () {
    $domain = new Domain('example.com');
    $this->assertFalse($domain->isIdn());

    $domain = new Domain('ex-ample.com');
    $this->assertFalse($domain->isIdn());

    $domain = new Domain('xn--mnnersalon-q5a.at'); // männersalon.at
    $this->assertTrue($domain->isIdn());

    $domain = new Domain('xn--mller-kva.de'); // müller.de
    $this->assertTrue($domain->isIdn());
});
