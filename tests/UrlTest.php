<?php
declare(strict_types=1);

use Crwlr\Url\Exceptions\InvalidUrlException;
use Crwlr\Url\Url;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    /**
     * @throws InvalidUrlException
     */
    public function testCanBeCreatedFromValidUrl()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertInstanceOf(Url::class, $url);
    }

    /**
     * @throws InvalidUrlException
     */
    public function testInvalidUrlThrowsException()
    {
        $this->expectException(InvalidUrlException::class);
        $url = new Url('https://');
    }

    /**
     * @throws InvalidUrlException
     */
    public function testCanBeCreatedFromRelativeUrl()
    {
        $url = new Url('/foo/bar?query=string');
        $this->assertInstanceOf(Url::class, $url);
    }

    /**
     * @throws InvalidUrlException
     */
    public function testCanBeCreatedViaFactoryMethod()
    {
        $url = Url::parse('http://www.example.com');
        $this->assertInstanceOf(Url::class, $url);
    }

    /**
     * @throws InvalidUrlException
     */
    public function testParseUrl()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('https', $url->scheme());
        $this->assertEquals('user', $url->user());
        $this->assertEquals('password', $url->password());
        $this->assertEquals('password', $url->pass());
        $this->assertEquals('sub.sub.example.com', $url->host());
        $this->assertEquals('example.com', $url->domain());
        $this->assertEquals('example', $url->domainLabel());
        $this->assertEquals('com', $url->domainSuffix());
        $this->assertEquals('sub.sub', $url->subdomain());
        $this->assertEquals(8080, $url->port());
        $this->assertEquals('/some/path', $url->path());
        $this->assertEquals('some=query', $url->query());
        $this->assertEquals(['some' => 'query'], $url->queryArray());
        $this->assertEquals('fragment', $url->fragment());
        $this->assertEquals('https://user:password@sub.sub.example.com:8080', $url->root());
        $this->assertEquals('/some/path?some=query#fragment', $url->relative());
    }

    /**
     * @throws InvalidUrlException
     */
    public function testClassPropertyAccess()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('https', $url->scheme);
        $this->assertEquals('user', $url->user);
        $this->assertEquals('password', $url->password);
        $this->assertEquals('password', $url->pass);
        $this->assertEquals('sub.sub.example.com', $url->host);
        $this->assertEquals('example.com', $url->domain);
        $this->assertEquals('example', $url->domainLabel);
        $this->assertEquals('com', $url->domainSuffix);
        $this->assertEquals('sub.sub', $url->subdomain);
        $this->assertEquals(8080, $url->port);
        $this->assertEquals('/some/path', $url->path);
        $this->assertEquals('some=query', $url->query);
        $this->assertEquals(['some' => 'query'], $url->queryArray);
        $this->assertEquals('fragment', $url->fragment);
        $this->assertEquals('https://user:password@sub.sub.example.com:8080', $url->root);
        $this->assertEquals('/some/path?some=query#fragment', $url->relative);

        // other class properties that aren't components of the parsed url should not be available.
        $this->assertNull($url->parser);
        $this->assertNull($url->validator);
        $this->assertNull($url->resolver);
    }

    /**
     * @throws InvalidUrlException
     */
    public function testParseIdnUrl()
    {
        $url = new Url('https://www.юбилейный.онлайн');
        $this->assertEquals('www.xn--90aiifajq6iua.xn--80asehdb', $url->host());
        $this->assertEquals('xn--80asehdb', $url->domainSuffix());
        $this->assertEquals('www', $url->subdomain());
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplaceScheme()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('https', $url->scheme());

        $url->scheme('http');
        $this->assertEquals('http', $url->scheme());
        $this->assertEquals(
            'http://user:password@sub.sub.example.com:8080/some/path?some=query#fragment',
            $url->toString()
        );
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplaceUser()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('user', $url->user());

        $url->user('differentuser');
        $this->assertEquals('differentuser', $url->user());
        $this->assertEquals(
            'https://differentuser:password@sub.sub.example.com:8080/some/path?some=query#fragment',
            $url->toString()
        );
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplacePassword()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('password', $url->password());
        $this->assertEquals('password', $url->pass());

        $url->password('differentpassword');
        $this->assertEquals('differentpassword', $url->password());
        $this->assertEquals('differentpassword', $url->pass());
        $this->assertEquals(
            'https://user:differentpassword@sub.sub.example.com:8080/some/path?some=query#fragment',
            $url->toString()
        );

        $url->pass('password');
        $this->assertEquals('password', $url->password());
        $this->assertEquals('password', $url->pass());
        $this->assertEquals(
            'https://user:password@sub.sub.example.com:8080/some/path?some=query#fragment',
            $url->toString()
        );
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplaceHost()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('sub.sub.example.com', $url->host());

        $url->host('some.host.xyz');
        $this->assertEquals('some.host.xyz', $url->host());
        $this->assertEquals('some', $url->subdomain());
        $this->assertEquals('host.xyz', $url->domain());
        $this->assertEquals('host', $url->domainLabel());
        $this->assertEquals('xyz', $url->domainSuffix());
        $this->assertEquals('https://user:password@some.host.xyz:8080/some/path?some=query#fragment', $url->toString());
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplaceSubdomain()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('sub.sub', $url->subdomain());

        $url->subdomain('www');
        $this->assertEquals('www', $url->subdomain());
        $this->assertEquals('www.example.com', $url->host());
        $this->assertEquals(
            'https://user:password@www.example.com:8080/some/path?some=query#fragment',
            $url->toString()
        );
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplaceDomain()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('example.com', $url->domain());

        $url->domain('eggsample.wtf');
        $this->assertEquals('eggsample.wtf', $url->domain());
        $this->assertEquals('wtf', $url->domainSuffix());
        $this->assertEquals('eggsample', $url->domainLabel());
        $this->assertEquals('sub.sub.eggsample.wtf', $url->host());
        $this->assertEquals(
            'https://user:password@sub.sub.eggsample.wtf:8080/some/path?some=query#fragment',
            $url->toString()
        );

        $url->domainLabel('xample');
        $this->assertEquals('xample', $url->domainLabel());
        $this->assertEquals('xample.wtf', $url->domain());
        $this->assertEquals('sub.sub.xample.wtf', $url->host());
        $this->assertEquals(
            'https://user:password@sub.sub.xample.wtf:8080/some/path?some=query#fragment',
            $url->toString()
        );
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplaceDomainSuffix()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('com', $url->domainSuffix());

        $url->domainSuffix('org');
        $this->assertEquals('org', $url->domainSuffix());
        $this->assertEquals('example.org', $url->domain());
        $this->assertEquals('sub.sub.example.org', $url->host());
        $this->assertEquals(
            'https://user:password@sub.sub.example.org:8080/some/path?some=query#fragment',
            $url->toString()
        );

        $url->domainSuffix('co.uk');
        $this->assertEquals('co.uk', $url->domainSuffix());
        $this->assertEquals('example.co.uk', $url->domain());
        $this->assertEquals('sub.sub.example.co.uk', $url->host());
        $this->assertEquals(
            'https://user:password@sub.sub.example.co.uk:8080/some/path?some=query#fragment',
            $url->toString()
        );
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplacePort()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals(8080, $url->port());

        $url->port(123);
        $this->assertEquals(123, $url->port());
        $this->assertEquals(
            'https://user:password@sub.sub.example.com:123/some/path?some=query#fragment',
            $url->toString()
        );
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplacePath()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('/some/path', $url->path());

        $url->path('/home');
        $this->assertEquals('/home', $url->path());
        $this->assertEquals(
            'https://user:password@sub.sub.example.com:8080/home?some=query#fragment',
            $url->toString()
        );
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplaceQueryString()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('some=query', $url->query());
        $this->assertEquals(['some' => 'query'], $url->queryArray());

        $url->query('foo=bar');
        $this->assertEquals('foo=bar', $url->query());
        $this->assertEquals(['foo' => 'bar'], $url->queryArray());
        $this->assertEquals(
            'https://user:password@sub.sub.example.com:8080/some/path?foo=bar#fragment',
            $url->toString()
        );

        $url->queryArray(['a' => 'b', 'c' => 'd']);
        $this->assertEquals('a=b&c=d', $url->query());
        $this->assertEquals(['a' => 'b', 'c' => 'd'], $url->queryArray());
        $this->assertEquals(
            'https://user:password@sub.sub.example.com:8080/some/path?a=b&c=d#fragment',
            $url->toString()
        );
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplaceFragment()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('fragment', $url->fragment());

        $url->fragment('test');
        $this->assertEquals('test', $url->fragment());
        $this->assertEquals(
            'https://user:password@sub.sub.example.com:8080/some/path?some=query#test',
            $url->toString()
        );
    }

    /**
     * @throws InvalidUrlException
     */
    public function testReplaceComponentsWithUnexpectedDataTypes()
    {
        $url = $this->createDefaultUrlObject();

        $url->user(1234);
        $this->assertEquals('1234', $url->user());

        $url->password(1234);
        $this->assertEquals('1234', $url->password());

        $url->domainLabel(1234);
        $this->assertEquals('1234', $url->domainLabel());

        $url->subdomain(1234);
        $this->assertEquals('1234', $url->subdomain());

        $url->port('8081');
        $this->assertEquals(8081, $url->port());
    }

    /**
     * @throws InvalidUrlException
     */
    public function testChainReplacementCalls()
    {
        $url = $this->createDefaultUrlObject();

        $url = $url->scheme('http')
            ->user('john')
            ->pass('god')
            ->subdomain('www')
            ->domainLabel('crwlr')
            ->domainSuffix('software')
            ->port(8081)
            ->path('/foo/bar')
            ->query('key=value')
            ->fragment('anchor');

        $this->assertInstanceOf(Url::class, $url);
        $this->assertEquals('http://john:god@www.crwlr.software:8081/foo/bar?key=value#anchor', $url->toString());
    }

    /**
     * @throws InvalidUrlException
     */
    public function testResolveRelativeUrl()
    {
        $url = $this->createDefaultUrlObject();

        $this->assertEquals(
            'https://user:password@sub.sub.example.com:8080/different/path',
            $url->resolve('/different/path')->toString()
        );

        // More tests on resolving relative to absolute urls => see ResolverTest.php
    }

    /**
     * @throws InvalidUrlException
     */
    public function testCompareUrls()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->compare($equalUrl, 'scheme'));
        $equalUrl->scheme('http');
        $this->assertFalse($url->compare($equalUrl, 'scheme'));

        $this->assertTrue($url->compare($equalUrl, 'user'));
        $equalUrl->user('usher');
        $this->assertFalse($url->compare($equalUrl, 'user'));

        $this->assertTrue($url->compare($equalUrl, 'pass'));
        $this->assertTrue($url->compare($equalUrl, 'password'));
        $equalUrl->pass('pass');
        $this->assertFalse($url->compare($equalUrl, 'pass'));
        $this->assertFalse($url->compare($equalUrl, 'password'));

        $this->assertTrue($url->compare($equalUrl, 'host'));
        $equalUrl->host('www.example.com');
        $this->assertFalse($url->compare($equalUrl, 'host'));
        $equalUrl->host('sub.sub.example.com');

        $this->assertTrue($url->compare($equalUrl, 'domain'));
        $equalUrl->domain('eggsample.com');
        $this->assertFalse($url->compare($equalUrl, 'domain'));

        $this->assertTrue($url->compare($equalUrl, 'domainSuffix'));
        $equalUrl->domainSuffix('org');
        $this->assertFalse($url->compare($equalUrl, 'domainSuffix'));

        $this->assertTrue($url->compare($equalUrl, 'subdomain'));
        $equalUrl->subdomain('www');
        $this->assertFalse($url->compare($equalUrl, 'subdomain'));

        $this->assertTrue($url->compare($equalUrl, 'port'));
        $equalUrl->port(123);
        $this->assertFalse($url->compare($equalUrl, 'port'));

        $this->assertTrue($url->compare($equalUrl, 'path'));
        $equalUrl->path('/different/path');
        $this->assertFalse($url->compare($equalUrl, 'path'));

        $this->assertTrue($url->compare($equalUrl, 'query'));
        $this->assertTrue($url->compare($equalUrl, 'queryArray'));
        $equalUrl->query('foo=bar');
        $this->assertFalse($url->compare($equalUrl, 'query'));
        $this->assertFalse($url->compare($equalUrl, 'queryArray'));

        $this->assertTrue($url->compare($equalUrl, 'fragment'));
        $equalUrl->fragment('foo');
        $this->assertFalse($url->compare($equalUrl, 'fragment'));

        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->compare($equalUrl, 'root'));
        $equalUrl->host('www.foo.org');
        $this->assertFalse($url->compare($equalUrl, 'root'));

        $this->assertTrue($url->compare($equalUrl, 'relative'));
        $equalUrl->path('/different/path');
        $this->assertFalse($url->compare($equalUrl, 'relative'));
    }

    /**
     * @return Url
     * @throws InvalidUrlException
     */
    private function createDefaultUrlObject()
    {
        $url = new Url('https://user:password@sub.sub.example.com:8080/some/path?some=query#fragment');
        return $url;
    }
}
