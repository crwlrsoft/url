<?php

use Crwlr\Url\Psr\Uri;

/** @var \PHPUnit\Framework\TestCase $this */

beforeEach(function () {
    $this->uri = new Uri('http://www.example.com/foo/bar?query=string#fragment');
});

test('Scheme', function () {
    $uri = $this->uri;
    $this->assertEquals('http', $uri->getScheme());

    $uri = $uri->withScheme('https');
    $this->assertEquals('https', $uri->getScheme());

    $uri = $uri->withScheme('HTTPS');
    $this->assertEquals('https', $uri->getScheme());

    $uri = $uri->withScheme('');
    $this->assertEquals('', $uri->getScheme());
    $this->assertEquals('//www.example.com/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withScheme('SFTP');
    $this->assertEquals('sftp', $uri->getScheme());
});

test('Authority', function () {
    $uri = $this->uri;
    $this->assertEquals('www.example.com', $uri->getAuthority());

    $uri = $uri->withUserInfo('crwlr', 'password');
    $this->assertEquals('crwlr:password@www.example.com', $uri->getAuthority());
    $this->assertEquals('http://crwlr:password@www.example.com/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withHost('foo.bar.baz.com');
    $this->assertEquals('crwlr:password@foo.bar.baz.com', $uri->getAuthority());

    $uri = $uri->withPort(1234);
    $this->assertEquals('crwlr:password@foo.bar.baz.com:1234', $uri->getAuthority());

    $uri = $uri->withHost('');
    $this->assertEquals('', $uri->getAuthority());
    $this->assertEquals('http:/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withHost('foo.bar.baz.com');
    $uri = $uri->withPort(80);
    $this->assertEquals('crwlr:password@foo.bar.baz.com', $uri->getAuthority());
    $this->assertEquals('http://crwlr:password@foo.bar.baz.com/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withUserInfo('');
    $uri = $uri->withHost('example.com');
    $this->assertEquals('example.com', $uri->getAuthority());
    $this->assertEquals('http://example.com/foo/bar?query=string#fragment', $uri->__toString());
});

test('UserInfo', function () {
    $uri = $this->uri;
    $this->assertEquals('', $uri->getUserInfo());

    $uri = $uri->withUserInfo('otsch', 'crwlr');
    $this->assertEquals('otsch:crwlr', $uri->getUserInfo());
    $this->assertEquals('otsch:crwlr@www.example.com', $uri->getAuthority());
    $this->assertEquals('http://otsch:crwlr@www.example.com/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withUserInfo('otsch', '');
    $this->assertEquals('otsch', $uri->getUserInfo());
    $this->assertEquals('otsch@www.example.com', $uri->getAuthority());
    $this->assertEquals('http://otsch@www.example.com/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withUserInfo('');
    $this->assertEquals('', $uri->getUserInfo());
    $this->assertEquals('www.example.com', $uri->getAuthority());
    $this->assertEquals('http://www.example.com/foo/bar?query=string#fragment', $uri->__toString());
});

test('Host', function () {
    $uri = $this->uri;
    $this->assertEquals('www.example.com', $uri->getHost());

    $uri = $uri->withHost('www.eggsample.com');
    $this->assertEquals('www.eggsample.com', $uri->getHost());

    $uri = $uri->withHost('');
    $this->assertEquals('', $uri->getHost());
    $this->assertEquals('', $uri->getAuthority());
    $uri = $uri->withUserInfo('otsch', 'crwlr');
    $this->assertEquals('', $uri->getAuthority());
    $uri = $uri->withUserInfo('');

    $uri = $uri->withHost('Sub.Domain.EXAMPLE.com');
    $this->assertEquals('sub.domain.example.com', $uri->getHost());
});

test('Port', function () {
    $uri = $this->uri;
    $this->assertNull($uri->getPort());

    $uri = $uri->withPort(2345);
    $this->assertEquals(2345, $uri->getPort());
    $this->assertEquals('www.example.com:2345', $uri->getAuthority());
    $this->assertEquals('http://www.example.com:2345/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withPort(80);
    // getPort() should return null when the defined port is the standard port for the current scheme.
    $this->assertNull($uri->getPort());
    $this->assertEquals('www.example.com', $uri->getAuthority());
    $this->assertEquals('http://www.example.com/foo/bar?query=string#fragment', $uri->__toString());

    // The previously set port should be retained (even though getPort() should return null), and when
    // the scheme is changed to a scheme where that port isn't the default, it should be returned by getPort().
    $uri = $uri->withScheme('https');
    $this->assertEquals(80, $uri->getPort());
    $this->assertEquals('www.example.com:80', $uri->getAuthority());
    $this->assertEquals('https://www.example.com:80/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withScheme('http');
    $uri = $uri->withPort(34567);
    $this->assertEquals(34567, $uri->getPort());
    $this->assertEquals('www.example.com:34567', $uri->getAuthority());
    $this->assertEquals('http://www.example.com:34567/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withPort(null);
    $this->assertNull($uri->getPort());
    $this->assertEquals('www.example.com', $uri->getAuthority());
    $this->assertEquals('http://www.example.com/foo/bar?query=string#fragment', $uri->__toString());
});

test('PortAboveRange', function () {
    $uri = $this->uri;
    $this->expectException(InvalidArgumentException::class);
    $uri->withPort(65536);
});

test('NegativePort', function () {
    $uri = $this->uri;
    $this->expectException(InvalidArgumentException::class);
    $uri->withPort(-1);
});

test('Path', function () {
    $uri = $this->uri;
    $this->assertEquals('/foo/bar', $uri->getPath());

    $uri = $uri->withPath('baz');
    $this->assertEquals('/foo/baz', $uri->getPath());
    $this->assertEquals('http://www.example.com/foo/baz?query=string#fragment', $uri->__toString());

    $uri = $uri->withPath('/bar/foo?baz=query#chapter3');
    $this->assertEquals('/bar/foo%3Fbaz=query%23chapter3', $uri->getPath());
    $this->assertEquals('http://www.example.com/bar/foo%3Fbaz=query%23chapter3?query=string#fragment', $uri->__toString());

    $uri = $uri->withPath('/foo%25bar');
    $this->assertEquals('/foo%25bar', $uri->getPath());

    $uri = $uri->withPath('//foo/bar');
    $this->assertEquals('//foo/bar', $uri->getPath());
    $this->assertEquals('http://www.example.com//foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withPath('.././../foo/bar');
    $this->assertEquals('/foo/bar', $uri->getPath());
    $this->assertEquals('http://www.example.com/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withPath('');
    $this->assertEquals('', $uri->getPath());
    $this->assertEquals('http://www.example.com?query=string#fragment', $uri->__toString());

    $uri = $uri->withPath('/');
    $this->assertEquals('/', $uri->getPath());
    $this->assertEquals('http://www.example.com/?query=string#fragment', $uri->__toString());
});

test('Query', function () {
    $uri = $this->uri;
    $this->assertEquals('query=string', $uri->getQuery());

    $uri = $uri->withQuery('key=value&key2=value2');
    $this->assertEquals('key=value&key2=value2', $uri->getQuery());

    $uri = $uri->withQuery('k.1=v.1&k.2[s.k1]=v.2&k.2[s.k2]=v.3');
    $this->assertEquals('k.1=v.1&k.2%5Bs.k1%5D=v.2&k.2%5Bs.k2%5D=v.3', $uri->getQuery());

    $uri = $uri->withQuery('');
    $this->assertEquals('', $uri->getQuery());
});

test('Fragment', function () {
    $uri = $this->uri;
    $this->assertEquals('fragment', $uri->getFragment());

    $uri = $uri->withFragment('differentfragment');
    $this->assertEquals('differentfragment', $uri->getFragment());
    $this->assertEquals('http://www.example.com/foo/bar?query=string#differentfragment', $uri->__toString());

    $uri = $uri->withFragment('');
    $this->assertEquals('', $uri->getFragment());
    $this->assertEquals('http://www.example.com/foo/bar?query=string', $uri->__toString());

    $uri = $uri->withFragment('foo');
    $this->assertEquals('foo', $uri->getFragment());
    $this->assertEquals('http://www.example.com/foo/bar?query=string#foo', $uri->__toString());

    $uri = $uri->withFragment('fragm€nt');
    $this->assertEquals('fragm%E2%82%ACnt', $uri->getFragment());

    $uri = $uri->withFragment('fragm%E2%82%ACnt');
    $this->assertEquals('fragm%E2%82%ACnt', $uri->getFragment());
});

test('ToString', function () {
    $uri = new Uri('/foo/bar?query=string#fragment');
    $this->assertEquals('', $uri->getScheme());
    $this->assertEquals('', $uri->getAuthority());
    $this->assertEquals('', $uri->getUserInfo());
    $this->assertNull($uri->getPort());
    $this->assertEquals('/foo/bar', $uri->getPath());
    $this->assertEquals('query=string', $uri->getQuery());
    $this->assertEquals('fragment', $uri->getFragment());
    $this->assertEquals('/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withScheme('https');
    $this->assertEquals('https:/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withHost('www.example.com');
    $this->assertEquals('https://www.example.com/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withScheme('');
    $this->assertEquals('//www.example.com/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withPath('///foo/bar');
    $this->assertEquals('//www.example.com///foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withHost('');
    $this->assertEquals('/foo/bar?query=string#fragment', $uri->__toString());

    $uri = $uri->withPath('');
    $this->assertEquals('?query=string#fragment', $uri->__toString());

    $uri = $uri->withQuery('');
    $this->assertEquals('#fragment', $uri->__toString());
});
