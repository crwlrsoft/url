<?php

use Crwlr\Url\Exceptions\InvalidUrlComponentException;
use Crwlr\Url\Exceptions\InvalidUrlException;
use Crwlr\Url\Psr\Uri;
use Crwlr\Url\Url;
use Psr\Http\Message\UriInterface;

/** @var \PHPUnit\Framework\TestCase $this */

test('CanBeCreatedFromValidUrl', function () {
    $url = createDefaultUrlObject();
    $this->assertInstanceOf(Url::class, $url);
});

test('InvalidUrlThrowsException', function () {
    $this->expectException(InvalidUrlException::class);
    new Url('https://');
});

test('CanBeCreatedFromRelativeUrl', function () {
    $url = new Url('/foo/bar?query=string');
    $this->assertInstanceOf(Url::class, $url);
});

test('CantBeCreatedFromRelativePath', function () {
    $url = new Url('yo/lo');
    $this->assertInstanceOf(Url::class, $url);
});

test('CanBeCreatedViaFactoryMethod', function () {
    $url = Url::parse('http://www.example.com');
    $this->assertInstanceOf(Url::class, $url);
});

test('Psr7FactoryMethodWithAbsoluteUrl', function () {
    $uri = Url::parsePsr7('https://www.crwlr.software/packages/url');
    $this->assertInstanceOf(Uri::class, $uri);
});

test('Psr7FactoryMethodWithRelativeReference', function () {
    $uri = Url::parsePsr7('/packages/url');
    $this->assertInstanceOf(Uri::class, $uri);
});

test('ParseUrl', function () {
    $url = createDefaultUrlObject();
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
});

test('ParseIdnUrl', function () {
    $url = new Url('https://www.юбилейный.онлайн');
    $this->assertEquals('www.xn--90aiifajq6iua.xn--80asehdb', $url->host());
    $this->assertEquals('xn--80asehdb', $url->domainSuffix());
    $this->assertEquals('www', $url->subdomain());
});

test('UrlWithInvalidHost', function () {
    $this->expectException(InvalidUrlException::class);
    Url::parse('https://www.exclamation!mark.co');
});

test('ReplaceScheme', function () {
    $url = createDefaultUrlObject();
    $this->assertEquals('https', $url->scheme());

    $url->scheme('http');
    $this->assertEquals('http', $url->scheme());
    $this->assertEquals(
        'http://user:password@sub.sub.example.com:8080/some/path?some=query#fragment',
        $url->toString()
    );
});

test('SetInvalidSchemeThrowsException', function () {
    $url = createDefaultUrlObject();
    $this->expectException(InvalidUrlComponentException::class);
    $url->scheme('1nvalidSch3m3');
});

test('SchemeContainingPlus', function () {
    $url = Url::parse('coap+tcp://example.com');
    $this->assertEquals('coap+tcp', $url->scheme());
});

test('SchemeContainingDash', function () {
    $url = Url::parse('chrome-extension://extension-id/page.html');
    $this->assertEquals('chrome-extension', $url->scheme());
});

test('SchemeContainingDot', function () {
    $url = Url::parse('soap.beep://stockquoteserver.example.com/StockQuote');
    $this->assertEquals('soap.beep', $url->scheme());
});

test('ParseUrlWithoutScheme', function () {
    $url = Url::parse('//www.example.com/test.html');
    $this->assertEquals('//www.example.com/test.html', $url->toString());
    $this->assertEquals('www.example.com', $url->host());
    $url->scheme('http');
    $this->assertEquals('http://www.example.com/test.html', $url->toString());
});

test('ReplaceAuthority', function () {
    $url = createDefaultUrlObject();
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
});

test('SetInvalidAuthorityThrowsException', function () {
    $url = createDefaultUrlObject();
    $this->expectException(InvalidUrlComponentException::class);
    $url->authority('example.com:100000');
});

test('ReplaceUser', function () {
    $url = createDefaultUrlObject();
    $this->assertEquals('user', $url->user());

    $url->user('differentuser');
    $this->assertEquals('differentuser', $url->user());
    $this->assertEquals(
        'https://differentuser:password@sub.sub.example.com:8080/some/path?some=query#fragment',
        $url->toString()
    );
});

test('ReplacePassword', function () {
    $url = createDefaultUrlObject();
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
});

test('ReplaceUserInfo', function () {
    $url = createDefaultUrlObject();
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
});

test('UrlWithEmptyUserInfo', function () {
    $url = Url::parse('https://@example.com');
    $this->assertEquals('https://example.com', $url->toString());
    $this->assertEquals('', $url->userInfo());
});

test('ReplaceHost', function () {
    $url = createDefaultUrlObject();
    $this->assertEquals('sub.sub.example.com', $url->host());

    $url->host('some.host.xyz');
    $this->assertEquals('some.host.xyz', $url->host());
    $this->assertEquals('some', $url->subdomain());
    $this->assertEquals('host.xyz', $url->domain());
    $this->assertEquals('host', $url->domainLabel());
    $this->assertEquals('xyz', $url->domainSuffix());
    $this->assertEquals('https://user:password@some.host.xyz:8080/some/path?some=query#fragment', $url->toString());
});

test('SetInvalidHostThrowsException', function () {
    $url = createDefaultUrlObject();
    $this->expectException(InvalidUrlComponentException::class);
    $url->host('crw!r.software');
});

test('PercentEncodedCharactersInHost', function () {
    $url = Url::parse('https://www.m%C3%A4nnersalon.at');
    $this->assertEquals('www.xn--mnnersalon-q5a.at', $url->host());
});

test('IpAddressHost', function () {
    $url = Url::parse('https://192.168.0.1/foo/bar');
    $this->assertEquals('192.168.0.1', $url->host());
    $this->assertEquals('https://192.168.0.1/foo/bar', $url->toString());

    $url = Url::parse('https://[192.0.2.16]:80/foo/bar');
    $this->assertEquals('[192.0.2.16]', $url->host());
    $this->assertEquals('https://[192.0.2.16]:80/foo/bar', $url->toString());
});

/**
 * Example addresses from https://tools.ietf.org/html/rfc2732#section-2
 *
 */
test('IpV6AddressHost', function () {
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
});

test('ReplaceSubdomain', function () {
    // https://user:password@sub.sub.example.com:8080/some/path?some=query#fragment
    $url = createDefaultUrlObject();
    $this->assertEquals('sub.sub', $url->subdomain());

    $url->subdomain('www');
    $this->assertEquals('www', $url->subdomain());
    $this->assertEquals('www.example.com', $url->host());
    $this->assertEquals(
        'https://user:password@www.example.com:8080/some/path?some=query#fragment',
        $url->toString()
    );
});

test('EmptySubdomain', function () {
    $this->assertNull(Url::parse('https://crwlr.software')->subdomain());

    $this->assertEquals('www', Url::parse('https://www.crwlr.software')->subdomain());
});

test('SetInvalidSubdomainThrowsException', function () {
    $url = createDefaultUrlObject();
    $this->expectException(InvalidUrlComponentException::class);
    $url->subdomain('crw!r');
});

test('ReplaceDomain', function () {
    $url = createDefaultUrlObject();
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
});

test('SetInvalidDomainThrowsException', function () {
    $url = createDefaultUrlObject();
    $this->expectException(InvalidUrlComponentException::class);
    $url->domain('"example".com');
});

test('ReplaceDomainLabel', function () {
    $url = createDefaultUrlObject();
    $this->assertEquals('example', $url->domainLabel());

    $url->domainLabel('eggsample');
    $this->assertEquals('eggsample', $url->domainLabel());
    $this->assertEquals('eggsample.com', $url->domain());
    $this->assertEquals('sub.sub.eggsample.com', $url->host());
});

test('SetInvalidDomainLabelThrowsException', function () {
    $url = createDefaultUrlObject();
    $this->expectException(InvalidUrlComponentException::class);
    $url->domainLabel('invalid.label');
});

test('ReplaceDomainSuffix', function () {
    $url = createDefaultUrlObject();
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
});

test('SetInvalidDomainSuffixThrowsException', function () {
    $url = createDefaultUrlObject();
    $this->expectException(InvalidUrlComponentException::class);
    $url->domainSuffix('invalid.suffix');
});

test('ReplacePort', function () {
    $url = createDefaultUrlObject();
    $this->assertEquals(8080, $url->port());

    $url->port(123);
    $this->assertEquals(123, $url->port());
    $this->assertEquals(
        'https://user:password@sub.sub.example.com:123/some/path?some=query#fragment',
        $url->toString()
    );
});

test('SetInvalidPortThrowsException', function () {
    $url = createDefaultUrlObject();
    $this->expectException(InvalidUrlComponentException::class);
    $url->port(-3);
});

test('UrlWithEmptyPort', function () {
    $url = Url::parse('https://www.example.com:/foo/bar');
    $this->assertEquals('https://www.example.com/foo/bar', $url->toString());
    $this->assertEquals(null, $url->port());
});

test('ReplacePath', function () {
    $url = createDefaultUrlObject();
    $this->assertEquals('/some/path', $url->path());

    $url->path('/home');
    $this->assertEquals('/home', $url->path());
    $this->assertEquals(
        'https://user:password@sub.sub.example.com:8080/home?some=query#fragment',
        $url->toString()
    );
});

test('ParseUrlWithEmptyPath', function () {
    $url = Url::parse('https://www.example.com?foo=bar');
    $this->assertEquals('foo=bar', $url->query());
    $this->assertNull($url->path());
    $this->assertEquals('https://www.example.com?foo=bar', $url->toString());

    $url = Url::parse('https://www.example.com#foo');
    $this->assertEquals('foo', $url->fragment());
    $this->assertEquals('https://www.example.com#foo', $url->toString());
});

it('parses a query string with percent encoded brackets and keys containing dots', function () {
    $url = Url::parse('https://www.example.com/path?foo.bar%5B0%5D=v1&foo.bar_extra%5B0%5D=v2&foo.bar.extra%5B0%5D=v3');

    expect($url->queryArray())->toBe([
        'foo.bar' => ['v1'],
        'foo.bar_extra' => ['v2'],
        'foo.bar.extra' => ['v3'],
    ]);
});

it('correctly parses a query where keys contain dots', function () {
    $url = Url::parse('https://www.example.com/path?foo.bar[0]=v1&foo.bar_extra[0]=v2&foo.bar.extra[0]=v3');

    expect($url->query())->toBe('foo.bar%5B0%5D=v1&foo.bar_extra%5B0%5D=v2&foo.bar.extra%5B0%5D=v3');

    expect($url->queryArray())->toBe([
        'foo.bar' => ['v1'],
        'foo.bar_extra' => ['v2'],
        'foo.bar.extra' => ['v3'],
    ]);
});

test('ReplaceQueryString', function () {
    $url = createDefaultUrlObject();
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
});

test('ParseUrlWithEmptyQuery', function () {
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
});

test('ReplaceFragment', function () {
    $url = createDefaultUrlObject();
    $this->assertEquals('fragment', $url->fragment());

    $url->fragment('test');
    $this->assertEquals('test', $url->fragment());
    $this->assertEquals(
        'https://user:password@sub.sub.example.com:8080/some/path?some=query#test',
        $url->toString()
    );
});

test('ParseUrlWithEmptyFragment', function () {
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
});

test('ChainReplacementCalls', function () {
    $url = createDefaultUrlObject();

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
});

test('ParseRelativeReferences', function () {
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
});

test('IsRelativeReference', function () {
    $url = Url::parse('/relative/reference?query=string#fragment');
    $this->assertTrue($url->isRelativeReference());

    $url = Url::parse('//www.example.com/relative/reference');
    $this->assertTrue($url->isRelativeReference());

    $url = Url::parse('relative/reference');
    $this->assertTrue($url->isRelativeReference());

    $url = Url::parse('https://www.example.com');
    $this->assertFalse($url->isRelativeReference());
});

test('ResolveRelativeReference', function () {
    $url = createDefaultUrlObject();

    $this->assertEquals(
        'https://user:password@sub.sub.example.com:8080/different/path',
        $url->resolve('/different/path')->toString()
    );

    // More tests on resolving relative to absolute URLs => see ResolverTest.php
});

test('CompareUrls', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isEqualTo($equalUrl->__toString()));
    $equalUrl->port(1);
    $this->assertFalse($url->isEqualTo($equalUrl->__toString()));
});

test('CompareScheme', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'scheme'));
    $this->assertTrue($url->isSchemeEqualIn($equalUrl));
    $equalUrl->scheme('http');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'scheme'));
    $this->assertFalse($url->isSchemeEqualIn($equalUrl));
});

test('CompareAuthority', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'authority'));
    $this->assertTrue($url->isAuthorityEqualIn($equalUrl));
    $equalUrl->authority('sub.sub.example.com');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'authority'));
    $this->assertFalse($url->isAuthorityEqualIn($equalUrl));
});

test('CompareUser', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'user'));
    $this->assertTrue($url->isUserEqualIn($equalUrl));
    $equalUrl->user('usher');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'user'));
    $this->assertFalse($url->isUserEqualIn($equalUrl));
});

test('ComparePassword', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'pass'));
    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'password'));
    $this->assertTrue($url->isPasswordEqualIn($equalUrl));
    $equalUrl->pass('pass');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'pass'));
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'password'));
    $this->assertFalse($url->isPasswordEqualIn($equalUrl));
});

test('CompareUserInfo', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'userInfo'));
    $this->assertTrue($url->isUserInfoEqualIn($equalUrl));
    $equalUrl->userInfo('u§3r:p455w0rd');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'userInfo'));
    $this->assertFalse($url->isUserInfoEqualIn($equalUrl));
});

test('CompareHost', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'host'));
    $this->assertTrue($url->isHostEqualIn($equalUrl));
    $equalUrl->host('www.example.com');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'host'));
    $this->assertFalse($url->isHostEqualIn($equalUrl));
    $equalUrl->host('sub.sub.example.com');
});

test('CompareDomain', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domain'));
    $this->assertTrue($url->isDomainEqualIn($equalUrl));
    $equalUrl->domain('eggsample.com');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domain'));
    $this->assertFalse($url->isDomainEqualIn($equalUrl));
    $equalUrl->domain('example.com');
});

test('CompareDomainLabel', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domainLabel'));
    $this->assertTrue($url->isDomainLabelEqualIn($equalUrl));
    $equalUrl->domainLabel('eggsample');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domainLabel'));
    $this->assertFalse($url->isDomainLabelEqualIn($equalUrl));
});

test('CompareDomainSuffix', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domainSuffix'));
    $this->assertTrue($url->isDomainSuffixEqualIn($equalUrl));
    $equalUrl->domainSuffix('org');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domainSuffix'));
    $this->assertFalse($url->isDomainSuffixEqualIn($equalUrl));
});

test('CompareSubdomain', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'subdomain'));
    $this->assertTrue($url->isSubdomainEqualIn($equalUrl));
    $equalUrl->subdomain('www');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'subdomain'));
    $this->assertFalse($url->isSubdomainEqualIn($equalUrl));
});

test('ComparePort', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'port'));
    $this->assertTrue($url->isPortEqualIn($equalUrl));
    $equalUrl->port(123);
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'port'));
    $this->assertFalse($url->isPortEqualIn($equalUrl));
});

test('ComparePath', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'path'));
    $this->assertTrue($url->isPathEqualIn($equalUrl));
    $equalUrl->path('/different/path');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'path'));
    $this->assertFalse($url->isPathEqualIn($equalUrl));
});

test('CompareQuery', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'query'));
    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'queryArray'));
    $this->assertTrue($url->isQueryEqualIn($equalUrl));
    $equalUrl->query('foo=bar');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'query'));
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'queryArray'));
    $this->assertFalse($url->isQueryEqualIn($equalUrl));
});

test('CompareFragment', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'fragment'));
    $this->assertTrue($url->isFragmentEqualIn($equalUrl));
    $equalUrl->fragment('foo');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'fragment'));
    $this->assertFalse($url->isFragmentEqualIn($equalUrl));
});

test('CompareRoot', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'root'));
    $equalUrl->host('www.foo.org');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'root'));
});

test('CompareRelative', function () {
    $url = createDefaultUrlObject();
    $equalUrl = createDefaultUrlObject();

    $this->assertTrue($url->isComponentEqualIn($equalUrl, 'relative'));
    $equalUrl->path('/different/path');
    $this->assertFalse($url->isComponentEqualIn($equalUrl, 'relative'));
});

/**
 * Special characters in user information will be percent encoded.
 */
test('UrlWithSpecialCharactersInUserInfo', function () {
    $url = Url::parse('https://u§er:pássword@example.com');
    $this->assertEquals('https://u%C2%A7er:p%C3%A1ssword@example.com', $url->toString());
});

/**
 * Parsing URLs containing special characters like umlauts in path, query or fragment percent encodes these
 * characters.
 */
test('ParsingUrlsContainingUmlauts', function () {
    $url = Url::parse('https://www.example.com/bürokaufmann');
    $this->assertEquals('https://www.example.com/b%C3%BCrokaufmann', $url->toString());
    $url = Url::parse('https://www.example.com/path?quäry=strüng');
    $this->assertEquals('https://www.example.com/path?qu%C3%A4ry=str%C3%BCng', $url->toString());
    $url = Url::parse('https://www.example.com/path#frägment');
    $this->assertEquals('https://www.example.com/path#fr%C3%A4gment', $url->toString());
});

/**
 * Percent characters from percent encoded characters must not be (double) encoded.
 */
test('EncodingPercentEncodedCharacters', function () {
    $url = Url::parse('https://www.example.com/b%C3%BCrokaufmann');
    $this->assertEquals('https://www.example.com/b%C3%BCrokaufmann', $url->toString());
    $url = Url::parse('https://www.example.com/just%-character');
    $this->assertEquals('https://www.example.com/just%25-character', $url->toString());
});

test('HasIdn', function () {
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
});

test('CreateRelativePathReferenceWithAuthority', function () {
    $url = Url::parse('relative/reference');
    $url->scheme('https');

    $this->expectException(InvalidUrlComponentException::class);
    $url->authority('www.example.com');
});

test('ParseUrlWithSchemeAndPathButWithoutAuthority', function () {
    $url = Url::parse('http:/foo/bar');
    $this->assertEquals('http', $url->scheme());
    $this->assertEquals('/foo/bar', $url->path());
    $this->assertEquals('http:/foo/bar', $url->toString());

    $url = Url::parse('http:path#fragment');
    $this->assertEquals('http', $url->scheme());
    $this->assertEquals('path', $url->path());
    $this->assertEquals('fragment', $url->fragment());
    $this->assertEquals('http:path#fragment', $url->toString());
});

test('ParseUrlWithoutSchemeAndPathButPortQueryAndFragment', function () {
    $url = Url::parse('//www.example.com:80?query=string#fragment');
    $this->assertEquals('www.example.com', $url->host());
    $this->assertEquals(80, $url->port());
    $this->assertEquals('query=string', $url->query());
    $this->assertEquals('fragment', $url->fragment());
    $this->assertEquals('//www.example.com:80?query=string#fragment', $url->toString());
});

test('ParseUrlWithEmptyQueryAndFragment', function () {
    $url = Url::parse('https://www.example.com/?#');
    $this->assertEquals('/', $url->path());
    $this->assertNull($url->query());
    $this->assertNull($url->fragment());
    $this->assertEquals('https://www.example.com/', $url->toString());

    $url = Url::parse('https://www.example.com?#');
    $this->assertNull($url->query());
    $this->assertNull($url->fragment());
    $this->assertEquals('https://www.example.com', $url->toString());
});

test('ParseUrlWithHostWithTrailingDot', function () {
    $url = Url::parse('https://www.example.com./path');
    $this->assertEquals('www.example.com.', $url->host());
    $this->assertEquals('/path', $url->path());
    $this->assertEquals('https://www.example.com./path', $url->toString());
});

test('ParseUrlWithPathInFragment', function () {
    $url = Url::parse('https://www.example.com#fragment/foo/bar');
    $this->assertEquals('fragment/foo/bar', $url->fragment());
    $this->assertEquals('https://www.example.com#fragment/foo/bar', $url->toString());
});

test('ParseUrlWithColonInPath', function () {
    $url = Url::parse('https://www.example.com/path:foo/bar');
    $this->assertEquals('/path:foo/bar', $url->path());
    $this->assertEquals('https://www.example.com/path:foo/bar', $url->toString());
});

test('CreateRelativePathReferenceWithHost', function () {
    $url = Url::parse('relative/reference');
    $url->scheme('https');

    $this->expectException(InvalidUrlComponentException::class);
    $url->host('www.example.com');
});

test('EncodingEdgeCases', function () {
    $url = Url::parse('https://u§er:pássword@ком.香格里拉.電訊盈科:1234/föô/bár bàz?quär.y=strïng#frägmänt');
    $this->assertEquals(
        'https://u%C2%A7er:p%C3%A1ssword@xn--j1aef.xn--5su34j936bgsg.xn--fzys8d69uvgm:1234/f%C3%B6%C3%B4/' .
        'b%C3%A1r%20b%C3%A0z?qu%C3%A4r.y=str%C3%AFng#fr%C3%A4gm%C3%A4nt',
        $url->__toString()
    );
});

it('converts an Url instance into a PSR-7 compatible instance', function () {
    $url = Url::parse('https://www.crwl.io/en/home');

    expect($url)
        ->not()
        ->toBeInstanceOf(UriInterface::class)
        ->and($url->toPsr7())
        ->toBeInstanceOf(UriInterface::class);
});

function createDefaultUrlObject(): Url
{
    return new Url('https://user:password@sub.sub.example.com:8080/some/path?some=query#fragment');
}
