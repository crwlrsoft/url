<?php
declare(strict_types=1);

namespace Tests;

use Crwlr\Url\Exceptions\InvalidUrlComponentException;
use Crwlr\Url\Exceptions\InvalidUrlException;
use Crwlr\Url\Psr\Uri;
use Crwlr\Url\Url;
use Exception;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    public function testCanBeCreatedFromValidUrl(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->assertInstanceOf(Url::class, $url);
    }

    /**
     * @throws InvalidUrlException
     */
    public function testInvalidUrlThrowsException(): void
    {
        $this->expectException(InvalidUrlException::class);
        new Url('https://');
    }

    public function testCanBeCreatedFromRelativeUrl(): void
    {
        $url = new Url('/foo/bar?query=string');
        $this->assertInstanceOf(Url::class, $url);
    }

    public function testCantBeCreatedFromRelativePath(): void
    {
        $url = new Url('yo/lo');
        $this->assertInstanceOf(Url::class, $url);
    }

    public function testCanBeCreatedViaFactoryMethod(): void
    {
        $url = Url::parse('http://www.example.com');
        $this->assertInstanceOf(Url::class, $url);
    }

    public function testPsr7FactoryMethodWithAbsoluteUrl(): void
    {
        $uri = Url::parsePsr7('https://www.crwlr.software/packages/url');
        $this->assertInstanceOf(Uri::class, $uri);
    }

    public function testPsr7FactoryMethodWithRelativeReference(): void
    {
        $uri = Url::parsePsr7('/packages/url');
        $this->assertInstanceOf(Uri::class, $uri);
    }

    /**
     * @throws Exception
     */
    public function testParseUrl(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('https', $url->scheme());
        $this->assertEquals('user:password@sub.sub.example.com:8080', $url->authority());
        $this->assertEquals('user', $url->user());
        $this->assertEquals('password', $url->password());
        $this->assertEquals('password', $url->pass());
        $this->assertEquals('user:password', $url->userInfo());
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
     * @throws Exception
     */
    public function testParseIdnUrl(): void
    {
        $url = new Url('https://www.юбилейный.онлайн');
        $this->assertEquals('www.xn--90aiifajq6iua.xn--80asehdb', $url->host());
        $this->assertEquals('xn--80asehdb', $url->domainSuffix());
        $this->assertEquals('www', $url->subdomain());
    }

    /**
     * @throws InvalidUrlException
     */
    public function testUrlWithInvalidHost(): void
    {
        $this->expectException(InvalidUrlException::class);
        Url::parse('https://www.exclamation!mark.co');
    }

    /**
     * @throws Exception
     */
    public function testReplaceScheme(): void
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
     * @throws InvalidUrlComponentException
     * @throws Exception
     */
    public function testSetInvalidSchemeThrowsException(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->expectException(InvalidUrlComponentException::class);
        $url->scheme('1nvalidSch3m3');
    }

    /**
     * @throws Exception
     */
    public function testSchemeContainingPlus(): void
    {
        $url = Url::parse('coap+tcp://example.com');
        $this->assertEquals('coap+tcp', $url->scheme());
    }

    /**
     * @throws Exception
     */
    public function testSchemeContainingDash(): void
    {
        $url = Url::parse('chrome-extension://extension-id/page.html');
        $this->assertEquals('chrome-extension', $url->scheme());
    }

    /**
     * @throws Exception
     */
    public function testSchemeContainingDot(): void
    {
        $url = Url::parse('soap.beep://stockquoteserver.example.com/StockQuote');
        $this->assertEquals('soap.beep', $url->scheme());
    }

    /**
     * @throws Exception
     */
    public function testParseUrlWithoutScheme(): void
    {
        $url = Url::parse('//www.example.com/test.html');
        $this->assertEquals('//www.example.com/test.html', $url->toString());
        $this->assertEquals('www.example.com', $url->host());
        $url->scheme('http');
        $this->assertEquals('http://www.example.com/test.html', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testReplaceAuthority(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('user:password@sub.sub.example.com:8080', $url->authority());

        $url->authority('localhost:1234');
        $this->assertEquals('localhost:1234', $url->authority());
        $this->assertEquals('localhost', $url->host());
        $this->assertNull($url->userInfo());
        $this->assertEquals(1234, $url->port());
        $this->assertEquals('https://localhost:1234/some/path?some=query#fragment', $url->toString());

        $url->authority('4rn0ld:5chw4rz3n3gg3r@12.34.56.78');
        $this->assertEquals('4rn0ld:5chw4rz3n3gg3r@12.34.56.78', $url->authority());
        $this->assertEquals('12.34.56.78', $url->host());
        $this->assertEquals('4rn0ld:5chw4rz3n3gg3r', $url->userInfo());
        $this->assertEquals('4rn0ld', $url->user());
        $this->assertEquals('5chw4rz3n3gg3r', $url->password());
        $this->assertEquals(null, $url->port());
        $this->assertEquals(
            'https://4rn0ld:5chw4rz3n3gg3r@12.34.56.78/some/path?some=query#fragment',
            $url->toString()
        );

        $url->authority('');
        $this->assertEquals('', $url->authority());
        $this->assertNull($url->host());
        $this->assertNull($url->userInfo());
        $this->assertNull($url->user());
        $this->assertNull($url->password());
        $this->assertNull($url->port());
        $this->assertEquals('https:/some/path?some=query#fragment', $url->toString());

        $url->authority('www.crwlr.software');
        $this->assertEquals('www.crwlr.software', $url->authority());
        $this->assertEquals('www.crwlr.software', $url->host());
        $this->assertNull($url->userInfo());
        $this->assertNull($url->port());
        $this->assertEquals('https://www.crwlr.software/some/path?some=query#fragment', $url->toString());
    }

    /**
     * @throws InvalidUrlComponentException
     * @throws Exception
     */
    public function testSetInvalidAuthorityThrowsException(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->expectException(InvalidUrlComponentException::class);
        $url->authority('example.com:100000');
    }

    /**
     * @throws Exception
     */
    public function testReplaceUser(): void
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
     * @throws Exception
     */
    public function testReplacePassword(): void
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
     * @throws Exception
     */
    public function testReplaceUserInfo(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('user:password', $url->userInfo());

        $url->userInfo('u$3r:p455/w0rd');
        $this->assertEquals('u$3r:p455%2Fw0rd', $url->userInfo());
        $this->assertEquals('u$3r', $url->user());
        $this->assertEquals('p455%2Fw0rd', $url->password());
        $this->assertEquals(
            'https://u$3r:p455%2Fw0rd@sub.sub.example.com:8080/some/path?some=query#fragment',
            $url->toString()
        );

        $url->userInfo('');
        $this->assertNull($url->userInfo());
        $this->assertNull($url->user());
        $this->assertNull($url->password());
        $this->assertEquals('https://sub.sub.example.com:8080/some/path?some=query#fragment', $url->toString());

        $url->userInfo('a:b:c');
        $this->assertEquals('a:b%3Ac', $url->userInfo());
        $this->assertEquals('a', $url->user());
        $this->assertEquals('b%3Ac', $url->password());
        $this->assertEquals('https://a:b%3Ac@sub.sub.example.com:8080/some/path?some=query#fragment', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testUrlWithEmptyUserInfo(): void
    {
        $url = Url::parse('https://@example.com');
        $this->assertEquals('https://example.com', $url->toString());
        $this->assertEquals('', $url->userInfo());
    }

    /**
     * @throws Exception
     */
    public function testReplaceHost(): void
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
     * @throws InvalidUrlComponentException
     * @throws Exception
     */
    public function testSetInvalidHostThrowsException(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->expectException(InvalidUrlComponentException::class);
        $url->host('crw!r.software');
    }

    /**
     * @throws Exception
     */
    public function testPercentEncodedCharactersInHost(): void
    {
        $url = Url::parse('https://www.m%C3%A4nnersalon.at');
        $this->assertEquals('www.xn--mnnersalon-q5a.at', $url->host());
    }

    /**
     * @throws Exception
     */
    public function testIpAddressHost(): void
    {
        $url = Url::parse('https://192.168.0.1/foo/bar');
        $this->assertEquals('192.168.0.1', $url->host());
        $this->assertEquals('https://192.168.0.1/foo/bar', $url->toString());

        $url = Url::parse('https://[192.0.2.16]:80/foo/bar');
        $this->assertEquals('[192.0.2.16]', $url->host());
        $this->assertEquals('https://[192.0.2.16]:80/foo/bar', $url->toString());
    }

    /**
     * Example addresses from https://tools.ietf.org/html/rfc2732#section-2
     *
     * @throws Exception
     */
    public function testIpV6AddressHost(): void
    {
        $url = Url::parse('http://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/index.html');
        // Host must be lowercased as stated in https://tools.ietf.org/html/rfc3986#section-3.2.2
        $this->assertEquals('[fedc:ba98:7654:3210:fedc:ba98:7654:3210]', $url->host());
        // Port 80 isn't contained in printed URL because it's the standard port for http.
        $this->assertEquals('http://[fedc:ba98:7654:3210:fedc:ba98:7654:3210]/index.html', $url->toString());

        $url = Url::parse('http://[1080:0:0:0:8:800:200C:417A]/index.html');
        $this->assertEquals('[1080:0:0:0:8:800:200c:417a]', $url->host());
        $this->assertEquals('http://[1080:0:0:0:8:800:200c:417a]/index.html', $url->toString());

        $url = Url::parse('http://[3ffe:2a00:100:7031::1]');
        $this->assertEquals('[3ffe:2a00:100:7031::1]', $url->host());
        $this->assertEquals('http://[3ffe:2a00:100:7031::1]', $url->toString());

        $url = Url::parse('http://[1080::8:800:200C:417A]/foo');
        $this->assertEquals('[1080::8:800:200c:417a]', $url->host());
        $this->assertEquals('http://[1080::8:800:200c:417a]/foo', $url->toString());

        $url = Url::parse('http://[::192.9.5.5]/ipng');
        $this->assertEquals('[::192.9.5.5]', $url->host());
        $this->assertEquals('http://[::192.9.5.5]/ipng', $url->toString());

        $url = Url::parse('http://[::FFFF:129.144.52.38]:80/index.html');
        $this->assertEquals('[::ffff:129.144.52.38]', $url->host());
        $this->assertEquals('http://[::ffff:129.144.52.38]/index.html', $url->toString());

        $url = Url::parse('http://[2010:836B:4179::836B:4179]');
        $this->assertEquals('[2010:836b:4179::836b:4179]', $url->host());
        $this->assertEquals('http://[2010:836b:4179::836b:4179]', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testReplaceSubdomain(): void
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
     * @throws InvalidUrlComponentException
     * @throws Exception
     */
    public function testSetInvalidSubdomainThrowsException(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->expectException(InvalidUrlComponentException::class);
        $url->subdomain('crw!r');
    }

    /**
     * @throws Exception
     */
    public function testReplaceDomain(): void
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
     * @throws InvalidUrlComponentException
     * @throws Exception
     */
    public function testSetInvalidDomainThrowsException(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->expectException(InvalidUrlComponentException::class);
        $url->domain('"example".com');
    }

    /**
     * @throws Exception
     */
    public function testReplaceDomainLabel(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('example', $url->domainLabel());

        $url->domainLabel('eggsample');
        $this->assertEquals('eggsample', $url->domainLabel());
        $this->assertEquals('eggsample.com', $url->domain());
        $this->assertEquals('sub.sub.eggsample.com', $url->host());
    }

    /**
     * @throws InvalidUrlComponentException
     * @throws Exception
     */
    public function testSetInvalidDomainLabelThrowsException(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->expectException(InvalidUrlComponentException::class);
        $url->domainLabel('invalid.label');
    }

    /**
     * @throws Exception
     */
    public function testReplaceDomainSuffix(): void
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
     * @throws InvalidUrlComponentException
     * @throws Exception
     */
    public function testSetInvalidDomainSuffixThrowsException(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->expectException(InvalidUrlComponentException::class);
        $url->domainSuffix('invalid.suffix');
    }

    /**
     * @throws Exception
     */
    public function testReplacePort(): void
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
     * @throws InvalidUrlComponentException
     * @throws Exception
     */
    public function testSetInvalidPortThrowsException(): void
    {
        $url = $this->createDefaultUrlObject();
        $this->expectException(InvalidUrlComponentException::class);
        $url->port(-3);
    }

    /**
     * @throws Exception
     */
    public function testUrlWithEmptyPort(): void
    {
        $url = Url::parse('https://www.example.com:/foo/bar');
        $this->assertEquals('https://www.example.com/foo/bar', $url->toString());
        $this->assertEquals(null, $url->port());
    }

    /**
     * @throws Exception
     */
    public function testReplacePath(): void
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
     * @throws Exception
     */
    public function testParseUrlWithEmptyPath(): void
    {
        $url = Url::parse('https://www.example.com?foo=bar');
        $this->assertEquals('foo=bar', $url->query());
        $this->assertNull($url->path());
        $this->assertEquals('https://www.example.com?foo=bar', $url->toString());

        $url = Url::parse('https://www.example.com#foo');
        $this->assertEquals('foo', $url->fragment());
        $this->assertEquals('https://www.example.com#foo', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testReplaceQueryString(): void
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
     * @throws Exception
     */
    public function testParseUrlWithEmptyQuery(): void
    {
        $url = Url::parse('https://www.example.com/path?#fragment');
        $this->assertEquals('/path', $url->path());
        $this->assertNull($url->query());
        $this->assertEquals('fragment', $url->fragment());
        $this->assertEquals('https://www.example.com/path#fragment', $url->toString());

        $url = Url::parse('https://www.example.com?#fragment');
        $this->assertNull($url->path());
        $this->assertNull($url->query());
        $this->assertEquals('fragment', $url->fragment());
        $this->assertEquals('https://www.example.com#fragment', $url->toString());

        $url = Url::parse('https://www.example.com?');
        $this->assertNull($url->path());
        $this->assertNull($url->query());
        $this->assertEquals('https://www.example.com', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testReplaceFragment(): void
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
     * @throws Exception
     */
    public function testParseUrlWithEmptyFragment(): void
    {
        $url = Url::parse('https://www.example.com/path?query=string#');
        $this->assertEquals('query=string', $url->query());
        $this->assertNull($url->fragment());
        $this->assertEquals('https://www.example.com/path?query=string', $url->toString());

        $url = Url::parse('https://www.example.com/path#');
        $this->assertEquals('/path', $url->path());
        $this->assertNull($url->fragment());
        $this->assertEquals('https://www.example.com/path', $url->toString());

        $url = Url::parse('https://www.example.com#');
        $this->assertNull($url->fragment());
        $this->assertEquals('https://www.example.com', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testChainReplacementCalls(): void
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
     * @throws Exception
     */
    public function testParseRelativeReferences(): void
    {
        $url = Url::parse('/path?query#fragment');
        $this->assertEquals('/path', $url->path());
        $this->assertEquals('query', $url->query());
        $this->assertEquals('fragment', $url->fragment());

        $url = Url::parse('path?query#fragment');
        $this->assertEquals('path', $url->path());
        $this->assertEquals('query', $url->query());
        $this->assertEquals('fragment', $url->fragment());

        $url = Url::parse('/path?query');
        $this->assertEquals('/path', $url->path());
        $this->assertEquals('query', $url->query());
        $this->assertNull($url->fragment());

        $url = Url::parse('?query#fragment');
        $this->assertNull($url->path());
        $this->assertEquals('query', $url->query());
        $this->assertEquals('fragment', $url->fragment());

        $url = Url::parse('path#fragment');
        $this->assertEquals('path', $url->path());
        $this->assertNull($url->query());
        $this->assertEquals('fragment', $url->fragment());

        $url = Url::parse('path');
        $this->assertEquals('path', $url->path());
        $this->assertNull($url->query());
        $this->assertNull($url->fragment());

        $url = Url::parse('?query');
        $this->assertNull($url->path());
        $this->assertEquals('query', $url->query());
        $this->assertNull($url->fragment());

        $url = Url::parse('#fragment');
        $this->assertNull($url->path());
        $this->assertNull($url->query());
        $this->assertEquals('fragment', $url->fragment());

        $url = Url::parse('../relative/path');
        $this->assertEquals('../relative/path', $url->path());
        $this->assertEquals('../relative/path', $url->toString());

        $url = Url::parse('https');
        $this->assertEquals('https', $url->path());
        $this->assertNull($url->scheme());
        $this->assertEquals('https', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testIsRelativeReference(): void
    {
        $url = Url::parse('/relative/reference?query=string#fragment');
        $this->assertTrue($url->isRelativeReference());

        $url = Url::parse('//www.example.com/relative/reference');
        $this->assertTrue($url->isRelativeReference());

        $url = Url::parse('relative/reference');
        $this->assertTrue($url->isRelativeReference());

        $url = Url::parse('https://www.example.com');
        $this->assertFalse($url->isRelativeReference());
    }

    /**
     * @throws Exception
     */
    public function testResolveRelativeReference(): void
    {
        $url = $this->createDefaultUrlObject();

        $this->assertEquals(
            'https://user:password@sub.sub.example.com:8080/different/path',
            $url->resolve('/different/path')->toString()
        );

        // More tests on resolving relative to absolute URLs => see ResolverTest.php
    }

    /**
     * @throws Exception
     */
    public function testCompareUrls(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isEqualTo($equalUrl->__toString()));
        $equalUrl->port(1);
        $this->assertFalse($url->isEqualTo($equalUrl->__toString()));
    }

    /**
     * @throws Exception
     */
    public function testCompareScheme(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'scheme'));
        $this->assertTrue($url->isSchemeEqualIn($equalUrl));
        $equalUrl->scheme('http');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'scheme'));
        $this->assertFalse($url->isSchemeEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testCompareAuthority(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'authority'));
        $this->assertTrue($url->isAuthorityEqualIn($equalUrl));
        $equalUrl->authority('sub.sub.example.com');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'authority'));
        $this->assertFalse($url->isAuthorityEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testCompareUser(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'user'));
        $this->assertTrue($url->isUserEqualIn($equalUrl));
        $equalUrl->user('usher');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'user'));
        $this->assertFalse($url->isUserEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testComparePassword(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'pass'));
        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'password'));
        $this->assertTrue($url->isPasswordEqualIn($equalUrl));
        $equalUrl->pass('pass');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'pass'));
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'password'));
        $this->assertFalse($url->isPasswordEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testCompareUserInfo(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'userInfo'));
        $this->assertTrue($url->isUserInfoEqualIn($equalUrl));
        $equalUrl->userInfo('u§3r:p455w0rd');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'userInfo'));
        $this->assertFalse($url->isUserInfoEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testCompareHost(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'host'));
        $this->assertTrue($url->isHostEqualIn($equalUrl));
        $equalUrl->host('www.example.com');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'host'));
        $this->assertFalse($url->isHostEqualIn($equalUrl));
        $equalUrl->host('sub.sub.example.com');
    }

    /**
     * @throws Exception
     */
    public function testCompareDomain(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domain'));
        $this->assertTrue($url->isDomainEqualIn($equalUrl));
        $equalUrl->domain('eggsample.com');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domain'));
        $this->assertFalse($url->isDomainEqualIn($equalUrl));
        $equalUrl->domain('example.com');
    }

    /**
     * @throws Exception
     */
    public function testCompareDomainLabel(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domainLabel'));
        $this->assertTrue($url->isDomainLabelEqualIn($equalUrl));
        $equalUrl->domainLabel('eggsample');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domainLabel'));
        $this->assertFalse($url->isDomainLabelEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testCompareDomainSuffix(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domainSuffix'));
        $this->assertTrue($url->isDomainSuffixEqualIn($equalUrl));
        $equalUrl->domainSuffix('org');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domainSuffix'));
        $this->assertFalse($url->isDomainSuffixEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testCompareSubdomain(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'subdomain'));
        $this->assertTrue($url->isSubdomainEqualIn($equalUrl));
        $equalUrl->subdomain('www');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'subdomain'));
        $this->assertFalse($url->isSubdomainEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testComparePort(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'port'));
        $this->assertTrue($url->isPortEqualIn($equalUrl));
        $equalUrl->port(123);
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'port'));
        $this->assertFalse($url->isPortEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testComparePath(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'path'));
        $this->assertTrue($url->isPathEqualIn($equalUrl));
        $equalUrl->path('/different/path');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'path'));
        $this->assertFalse($url->isPathEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testCompareQuery(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'query'));
        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'queryArray'));
        $this->assertTrue($url->isQueryEqualIn($equalUrl));
        $equalUrl->query('foo=bar');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'query'));
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'queryArray'));
        $this->assertFalse($url->isQueryEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testCompareFragment(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'fragment'));
        $this->assertTrue($url->isFragmentEqualIn($equalUrl));
        $equalUrl->fragment('foo');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'fragment'));
        $this->assertFalse($url->isFragmentEqualIn($equalUrl));
    }

    /**
     * @throws Exception
     */
    public function testCompareRoot(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'root'));
        $equalUrl->host('www.foo.org');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'root'));
    }

    /**
     * @throws Exception
     */
    public function testCompareRelative(): void
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'relative'));
        $equalUrl->path('/different/path');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'relative'));
    }

    /**
     * Special characters in user information will be percent encoded.
     */
    public function testUrlWithSpecialCharactersInUserInfo(): void
    {
        $url = Url::parse('https://u§er:pássword@example.com');
        $this->assertEquals('https://u%C2%A7er:p%C3%A1ssword@example.com', $url->toString());
    }

    /**
     * Parsing URLs containing special characters like umlauts in path, query or fragment percent encodes these
     * characters.
     */
    public function testParsingUrlsContainingUmlauts(): void
    {
        $url = Url::parse('https://www.example.com/bürokaufmann');
        $this->assertEquals('https://www.example.com/b%C3%BCrokaufmann', $url->toString());
        $url = Url::parse('https://www.example.com/path?quäry=strüng');
        $this->assertEquals('https://www.example.com/path?qu%C3%A4ry=str%C3%BCng', $url->toString());
        $url = Url::parse('https://www.example.com/path#frägment');
        $this->assertEquals('https://www.example.com/path#fr%C3%A4gment', $url->toString());
    }

    /**
     * Percent characters from percent encoded characters must not be (double) encoded.
     */
    public function testEncodingPercentEncodedCharacters(): void
    {
        $url = Url::parse('https://www.example.com/b%C3%BCrokaufmann');
        $this->assertEquals('https://www.example.com/b%C3%BCrokaufmann', $url->toString());
        $url = Url::parse('https://www.example.com/just%-character');
        $this->assertEquals('https://www.example.com/just%25-character', $url->toString());
    }

    public function testHasIdn(): void
    {
        $url = Url::parse('https://www.example.com');
        $this->assertFalse($url->hasIdn());

        $url = Url::parse('https://www.ex-ample.com');
        $this->assertFalse($url->hasIdn());

        $url = Url::parse('https://www.männersalon.at');
        $this->assertTrue($url->hasIdn());

        $url = Url::parse('https://jobs.müller.de');
        $this->assertTrue($url->hasIdn());

        $url = Url::parse('https://www.xn--mnnersalon-q5a.at');
        $this->assertTrue($url->hasIdn());

        $url = Url::parse('https://ärzte.example.com');
        $this->assertFalse($url->hasIdn());
    }

    /**
     * @throws InvalidUrlException
     * @throws Exception
     */
    public function testCreateRelativePathReferenceWithAuthority(): void
    {
        $url = Url::parse('relative/reference');
        $url->scheme('https');

        $this->expectException(InvalidUrlComponentException::class);
        $url->authority('www.example.com');
    }

    /**
     * @throws Exception
     */
    public function testParseUrlWithSchemeAndPathButWithoutAuthority(): void
    {
        $url = Url::parse('http:/foo/bar');
        $this->assertEquals('http', $url->scheme());
        $this->assertEquals('/foo/bar', $url->path());
        $this->assertEquals('http:/foo/bar', $url->toString());

        $url = Url::parse('http:path#fragment');
        $this->assertEquals('http', $url->scheme());
        $this->assertEquals('path', $url->path());
        $this->assertEquals('fragment', $url->fragment());
        $this->assertEquals('http:path#fragment', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testParseUrlWithoutSchemeAndPathButPortQueryAndFragment(): void
    {
        $url = Url::parse('//www.example.com:80?query=string#fragment');
        $this->assertEquals('www.example.com', $url->host());
        $this->assertEquals(80, $url->port());
        $this->assertEquals('query=string', $url->query());
        $this->assertEquals('fragment', $url->fragment());
        $this->assertEquals('//www.example.com:80?query=string#fragment', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testParseUrlWithEmptyQueryAndFragment(): void
    {
        $url = Url::parse('https://www.example.com/?#');
        $this->assertEquals('/', $url->path());
        $this->assertNull($url->query());
        $this->assertNull($url->fragment());
        $this->assertEquals('https://www.example.com/', $url->toString());

        $url = Url::parse('https://www.example.com?#');
        $this->assertNull($url->query());
        $this->assertNull($url->fragment());
        $this->assertEquals('https://www.example.com', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testParseUrlWithHostWithTrailingDot(): void
    {
        $url = Url::parse('https://www.example.com./path');
        $this->assertEquals('www.example.com.', $url->host());
        $this->assertEquals('/path', $url->path());
        $this->assertEquals('https://www.example.com./path', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testParseUrlWithPathInFragment(): void
    {
        $url = Url::parse('https://www.example.com#fragment/foo/bar');
        $this->assertEquals('fragment/foo/bar', $url->fragment());
        $this->assertEquals('https://www.example.com#fragment/foo/bar', $url->toString());
    }

    /**
     * @throws Exception
     */
    public function testParseUrlWithColonInPath(): void
    {
        $url = Url::parse('https://www.example.com/path:foo/bar');
        $this->assertEquals('/path:foo/bar', $url->path());
        $this->assertEquals('https://www.example.com/path:foo/bar', $url->toString());
    }

    /**
     * @throws InvalidUrlException
     * @throws Exception
     */
    public function testCreateRelativePathReferenceWithHost(): void
    {
        $url = Url::parse('relative/reference');
        $url->scheme('https');

        $this->expectException(InvalidUrlComponentException::class);
        $url->host('www.example.com');
    }

    public function testEncodingEdgeCases(): void
    {
        $url = Url::parse('https://u§er:pássword@ком.香格里拉.電訊盈科:1234/föô/bár bàz?quär.y=strïng#frägmänt');
        $this->assertEquals(
            'https://u%C2%A7er:p%C3%A1ssword@xn--j1aef.xn--5su34j936bgsg.xn--fzys8d69uvgm:1234/f%C3%B6%C3%B4/' .
            'b%C3%A1r%20b%C3%A0z?qu%C3%A4r.y=str%C3%AFng#fr%C3%A4gm%C3%A4nt',
            $url->__toString()
        );
    }

    private function createDefaultUrlObject(): Url
    {
        return new Url('https://user:password@sub.sub.example.com:8080/some/path?some=query#fragment');
    }
}
