<?php
declare(strict_types=1);

use Crwlr\Url\Host;
use PHPUnit\Framework\TestCase;

final class HostTest extends TestCase
{
    public function testParseHost()
    {
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

        $host = new Host('fóó.юбилейный.онлайн');
        $this->assertInstanceOf(Host::class, $host);
        $this->assertEquals('xn--f-vgaa.xn--90aiifajq6iua.xn--80asehdb', $host->__toString());
        $this->assertEquals('xn--f-vgaa', $host->subdomain());
        $this->assertEquals('xn--90aiifajq6iua.xn--80asehdb', $host->domain());
        $this->assertEquals('xn--90aiifajq6iua', $host->domainLabel());
        $this->assertEquals('xn--80asehdb', $host->domainSuffix());
    }

    public function testSubdomain()
    {
        $host = new Host('www.example.com');

        $host->subdomain('sub.domain');
        $this->assertEquals('sub.domain', $host->subdomain());
        $this->assertEquals('sub.domain.example.com', $host->__toString());

        $host->subdomain('');
        $this->assertEquals(null, $host->subdomain());
        $this->assertEquals('example.com', $host->__toString());

        $host->subdomain('foo.bar.yololo');
        $this->assertEquals('foo.bar.yololo', $host->subdomain());
        $this->assertEquals('foo.bar.yololo.example.com', $host->__toString());
    }

    public function testDomain()
    {
        $host = new Host('www.example.com');

        $host->domain('foo.bar');
        $this->assertEquals('foo.bar', $host->domain());
        $this->assertEquals('www.foo.bar', $host->__toString());

        $host->domain('');
        $this->assertEquals(null, $host->domain());
        $this->assertEquals('', $host->__toString());

        $host->domain('crwlr.software');
        $this->assertEquals('crwlr.software', $host->domain());
        $this->assertEquals('www.crwlr.software', $host->__toString());
    }

    public function testDomainLabel()
    {
        $host = new Host('www.example.com');

        $host->domainLabel('foo');
        $this->assertEquals('foo', $host->domainLabel());
        $this->assertEquals('foo.com', $host->domain());
        $this->assertEquals('www.foo.com', $host->__toString());

        $host->domainLabel('');
        $this->assertNull($host->domainLabel());
        $this->assertEquals('com', $host->domainSuffix());
        $this->assertNull($host->domain());
        $this->assertEquals('', $host->__toString());

        $host->domainLabel('google');
        $this->assertEquals('google', $host->domainLabel());
        $this->assertEquals('google.com', $host->domain());
        $this->assertEquals('www.google.com', $host->__toString());
    }

    public function testDomainSuffix()
    {
        $host = new Host('www.example.com');

        $host->domainSuffix('org');
        $this->assertEquals('org', $host->domainSuffix());
        $this->assertEquals('example.org', $host->domain());
        $this->assertEquals('www.example.org', $host->__toString());

        $host->domainSuffix('');
        $this->assertNull($host->domainSuffix());
        $this->assertEquals('example', $host->domainLabel());
        $this->assertNull($host->domain());
        $this->assertEquals('', $host->__toString());

        $host->domainSuffix('software');
        $this->assertEquals('software', $host->domainSuffix());
        $this->assertEquals('example.software', $host->domain());
        $this->assertEquals('www.example.software', $host->__toString());
    }
}
