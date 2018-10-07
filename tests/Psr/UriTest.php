<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UriTest extends TestCase
{
    public function testScheme()
    {
        $uri = $this->getUri();
        $this->assertEquals('http', $uri->getScheme());

        $uri = $uri->withScheme('https');
        $this->assertEquals('https', $uri->getScheme());

        $uri = $uri->withScheme('');
        $this->assertEquals('', $uri->getScheme());
        $this->assertEquals('//www.example.com/foo/bar?query=string#fragment', $uri->__toString());

        $uri = $uri->withScheme('SFTP');
        $this->assertEquals('sftp', $uri->getScheme());
    }

    public function testAuthority()
    {
        $uri = $this->getUri();
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
    }

    public function testUserInfo()
    {
        $uri = new \Crwlr\Url\Psr\Uri('http://www.example.com/foo/bar?query=string#fragment');
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
    }

    public function testHost()
    {
        $uri = new \Crwlr\Url\Psr\Uri('http://www.example.com/foo/bar?query=string#fragment');
        $this->assertEquals('www.example.com', $uri->getHost());

        $uri = $uri->withHost('www.eggsample.com');
        $this->assertEquals('www.eggsample.com', $uri->getHost());

        $uri = $uri->withHost('');
        $this->assertEquals('', $uri->getHost());
        $this->assertEquals('', $uri->getAuthority());
        $uri = $uri->withUserInfo('otsch', 'crwlr');
        $this->assertEquals('', $uri->getAuthority());
        $uri = $uri->withUserInfo('');

        $uri = $uri->withHost('sub.domain.example.com');
        $this->assertEquals('sub.domain.example.com', $uri->getHost());
    }

    public function testPort()
    {
        $uri = $this->getUri();
        $this->assertEquals(null, $uri->getPort());

        $uri = $uri->withPort(2345);
        $this->assertEquals(2345, $uri->getPort());
        $this->assertEquals('www.example.com:2345', $uri->getAuthority());
        $this->assertEquals('http://www.example.com:2345/foo/bar?query=string#fragment', $uri->__toString());

        $uri = $uri->withPort(80);
        // getPort() should return null when the defined port is the standard port for the current scheme.
        $this->assertEquals(null, $uri->getPort());
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
        $this->assertEquals(null, $uri->getPort());
        $this->assertEquals('www.example.com', $uri->getAuthority());
        $this->assertEquals('http://www.example.com/foo/bar?query=string#fragment', $uri->__toString());
    }

    public function testPortAboveRange()
    {
        $uri = $this->getUri();
        $this->expectException(InvalidArgumentException::class);
        $uri->withPort(65536);
    }

    public function testNegativePort()
    {
        $uri = $this->getUri();
        $this->expectException(InvalidArgumentException::class);
        $uri->withPort(-1);
    }

    public function testPath()
    {
        $uri = $this->getUri();
        $this->assertEquals('/foo/bar', $uri->getPath());

        $uri = $uri->withPath('baz');
        $this->assertEquals('/foo/baz', $uri->getPath());
        $this->assertEquals('http://www.example.com/foo/baz?query=string#fragment', $uri->__toString());

        $uri = $uri->withPath('/bar/foo?baz=query#chapter3');
        $this->assertEquals('/bar/foo%3Fbaz=query%23chapter3', $uri->getPath());
        $this->assertEquals(
            'http://www.example.com/bar/foo%3Fbaz=query%23chapter3?query=string#fragment',
            $uri->__toString()
        );

        $uri = $uri->withPath('//foo/bar');
        $this->assertEquals('//foo/bar', $uri->getPath());
        $this->assertEquals('http://www.example.com//foo/bar?query=string#fragment', $uri->__toString());

        $uri = $uri->withPath('.././../foo/bar');
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('http://www.example.com/foo/bar?query=string#fragment', $uri->__toString());

        $uri = $uri->withPath('');
        $this->assertEquals('', $uri->getPath());
        $this->assertEquals('http://www.example.com?query=string#fragment', $uri->__toString());
    }

    public function testQuery()
    {
        $uri = $this->getUri();
        $this->assertEquals('query=string', $uri->getQuery());

        $uri = $uri->withQuery('key=value&key2=value2');
        $this->assertEquals('key=value&key2=value2', $uri->getQuery());

        $uri = $uri->withQuery('k.1=v.1&k.2[s.k1]=v.2&k.2[s.k2]=v.3');
        $this->assertEquals('k.1=v.1&k.2[s.k1]=v.2&k.2[s.k2]=v.3', $uri->getQuery());
    }

    public function testFragment()
    {
        $uri = $this->getUri();
        $this->assertEquals('fragment', $uri->getFragment());

        $uri = $uri->withFragment('differentfragment');
        $this->assertEquals($uri->getFragment(), 'differentfragment');
        $this->assertEquals('http://www.example.com/foo/bar?query=string#differentfragment', $uri->__toString());

        $uri = $uri->withFragment('');
        $this->assertEquals($uri->getFragment(), '');
        $this->assertEquals('http://www.example.com/foo/bar?query=string', $uri->__toString());

        $uri = $uri->withFragment('foo');
        $this->assertEquals('foo', $uri->getFragment());
        $this->assertEquals('http://www.example.com/foo/bar?query=string#foo', $uri->__toString());
    }

    /**
     * @return \Crwlr\Url\Psr\Uri
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    private function getUri()
    {
        return new \Crwlr\Url\Psr\Uri('http://www.example.com/foo/bar?query=string#fragment');
    }
}
