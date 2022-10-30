<?php

use Crwlr\Url\Host;

test('ParseHost', function () {
    $host = new Host('www.example.com');
    $this->assertInstanceOf(Host::class, $host);
    $this->assertEquals('www.example.com', $host->__toString());
    $this->assertEquals('www', $host->subdomain());
    $this->assertEquals('example.com', $host->domain());
    $this->assertEquals('example', $host->domainLabel());
    $this->assertEquals('com', $host->domainSuffix());

    $host = new Host('www.test.local');
    $this->assertInstanceOf(Host::class, $host);
    $this->assertEquals('www.test.local', $host->__toString());
    $this->assertNull($host->domain());

    $host = new Host('xn--f-vgaa.xn--90aiifajq6iua.xn--80asehdb');
    $this->assertInstanceOf(Host::class, $host);
    $this->assertEquals('xn--f-vgaa.xn--90aiifajq6iua.xn--80asehdb', $host->__toString());
    $this->assertEquals('xn--f-vgaa', $host->subdomain());
    $this->assertEquals('xn--90aiifajq6iua.xn--80asehdb', $host->domain());
    $this->assertEquals('xn--90aiifajq6iua', $host->domainLabel());
    $this->assertEquals('xn--80asehdb', $host->domainSuffix());
});

test('Subdomain', function () {
    $host = new Host('www.example.com');

    $host->subdomain('sub.domain');
    $this->assertEquals('sub.domain', $host->subdomain());
    $this->assertEquals('sub.domain.example.com', $host->__toString());

    $host->subdomain('');
    $this->assertNull($host->subdomain());
    $this->assertEquals('example.com', $host->__toString());

    $host->subdomain('foo.bar.yololo');
    $this->assertEquals('foo.bar.yololo', $host->subdomain());
    $this->assertEquals('foo.bar.yololo.example.com', $host->__toString());
});

test('EmptySubdomain', function () {
    $host = new Host('crwlr.software');

    $this->assertNull($host->subdomain());

    $host = new Host('www.crwlr.software');

    $this->assertEquals('www', $host->subdomain());
});

test('Domain', function () {
    $host = new Host('www.example.com');

    $host->domain('foo.bar');
    $this->assertEquals('foo.bar', $host->domain());
    $this->assertEquals('www.foo.bar', $host->__toString());

    $host->domain('');
    $this->assertNull($host->domain());
    $this->assertEmpty($host->__toString());

    $host->domain('crwlr.software');
    $this->assertEquals('crwlr.software', $host->domain());
    $this->assertEquals('www.crwlr.software', $host->__toString());
});

test('DomainLabel', function () {
    $host = new Host('www.example.com');

    $host->domainLabel('foo');
    $this->assertEquals('foo', $host->domainLabel());
    $this->assertEquals('foo.com', $host->domain());
    $this->assertEquals('www.foo.com', $host->__toString());

    $host->domainLabel('');
    $this->assertNull($host->domainLabel());
    $this->assertEquals('com', $host->domainSuffix());
    $this->assertNull($host->domain());
    $this->assertEmpty($host->__toString());

    $host->domainLabel('google');
    $this->assertEquals('google', $host->domainLabel());
    $this->assertEquals('google.com', $host->domain());
    $this->assertEquals('www.google.com', $host->__toString());
});

test('DomainSuffix', function () {
    $host = new Host('www.example.com');

    $host->domainSuffix('org');
    $this->assertEquals('org', $host->domainSuffix());
    $this->assertEquals('example.org', $host->domain());
    $this->assertEquals('www.example.org', $host->__toString());

    $host->domainSuffix('');
    $this->assertNull($host->domainSuffix());
    $this->assertEquals('example', $host->domainLabel());
    $this->assertNull($host->domain());
    $this->assertEmpty($host->__toString());

    $host->domainSuffix('software');
    $this->assertEquals('software', $host->domainSuffix());
    $this->assertEquals('example.software', $host->domain());
    $this->assertEquals('www.example.software', $host->__toString());
});

test('HasIdn', function () {
    $host = new Host('www.example.com');
    $this->assertFalse($host->hasIdn());

    $host = new Host('www.ex-ample.com');
    $this->assertFalse($host->hasIdn());

    $host = new Host('www.xn--mnnersalon-q5a.at'); // www.männersalon.at
    $this->assertTrue($host->hasIdn());

    $host = new Host('jobs.xn--mller-kva.de'); // jobs.müller.de
    $this->assertTrue($host->hasIdn());
});
