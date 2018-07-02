<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testCanBeCreatedFromValidUrl()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertInstanceOf(\Crwlr\Url\Url::class, $url);
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testInvalidUrlThrowsException()
    {
        $this->expectException(\Crwlr\Url\Exceptions\InvalidUrlException::class);
        $url = new \Crwlr\Url\Url('this is not a valid url');
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testCanBeCreatedViaFactoryMethod()
    {
        $url = \Crwlr\Url\Url::parse('http://www.example.com');
        $this->assertInstanceOf(\Crwlr\Url\Url::class, $url);
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testParseUrl()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->scheme(), 'https');
        $this->assertEquals($url->user(), 'user');
        $this->assertEquals($url->password(), 'password');
        $this->assertEquals($url->pass(), 'password');
        $this->assertEquals($url->host(), 'sub.sub.example.com');
        $this->assertEquals($url->domain(), 'example.com');
        $this->assertEquals($url->domainLabel(), 'example');
        $this->assertEquals($url->domainSuffix(), 'com');
        $this->assertEquals($url->subdomain(), 'sub.sub');
        $this->assertEquals($url->port(), 8080);
        $this->assertEquals($url->path(), '/some/path');
        $this->assertEquals($url->query(), 'some=query');
        $this->assertEquals($url->queryArray(), ['some' => 'query']);
        $this->assertEquals($url->fragment(), 'fragment');
        $this->assertEquals($url->root(), 'https://user:password@sub.sub.example.com:8080');
        $this->assertEquals($url->relative(), '/some/path?some=query#fragment');
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testClassPropertyAccess()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->scheme, 'https');
        $this->assertEquals($url->user, 'user');
        $this->assertEquals($url->password, 'password');
        $this->assertEquals($url->pass, 'password');
        $this->assertEquals($url->host, 'sub.sub.example.com');
        $this->assertEquals($url->domain, 'example.com');
        $this->assertEquals($url->domainLabel, 'example');
        $this->assertEquals($url->domainSuffix, 'com');
        $this->assertEquals($url->subdomain, 'sub.sub');
        $this->assertEquals($url->port, 8080);
        $this->assertEquals($url->path, '/some/path');
        $this->assertEquals($url->query, 'some=query');
        $this->assertEquals($url->queryArray, ['some' => 'query']);
        $this->assertEquals($url->fragment, 'fragment');
        $this->assertEquals($url->root, 'https://user:password@sub.sub.example.com:8080');
        $this->assertEquals($url->relative, '/some/path?some=query#fragment');

        // other class properties that aren't components of the parsed url should not be available.
        $this->assertNull($url->parser);
        $this->assertNull($url->validator);
        $this->assertNull($url->resolver);
        $this->assertNull($url->isInitialized);
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testParseIdnUrl()
    {
        $url = new \Crwlr\Url\Url('https://www.юбилейный.онлайн');
        $this->assertEquals($url->host(), 'www.xn--90aiifajq6iua.xn--80asehdb');
        $this->assertEquals($url->domainSuffix(), 'xn--80asehdb');
        $this->assertEquals($url->subdomain(), 'www');
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplaceScheme()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->scheme(), 'https');

        $url->scheme('http');
        $this->assertEquals($url->scheme(), 'http');
        $this->assertEquals(
            $url->toString(),
            'http://user:password@sub.sub.example.com:8080/some/path?some=query#fragment'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplaceUser()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->user(), 'user');

        $url->user('differentuser');
        $this->assertEquals($url->user(), 'differentuser');
        $this->assertEquals(
            $url->toString(),
            'https://differentuser:password@sub.sub.example.com:8080/some/path?some=query#fragment'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplacePassword()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->password(), 'password');
        $this->assertEquals($url->pass(), 'password');

        $url->password('differentpassword');
        $this->assertEquals($url->password(), 'differentpassword');
        $this->assertEquals($url->pass(), 'differentpassword');
        $this->assertEquals(
            $url->toString(),
            'https://user:differentpassword@sub.sub.example.com:8080/some/path?some=query#fragment'
        );

        $url->pass('password');
        $this->assertEquals($url->password(), 'password');
        $this->assertEquals($url->pass(), 'password');
        $this->assertEquals(
            $url->toString(),
            'https://user:password@sub.sub.example.com:8080/some/path?some=query#fragment'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplaceHost()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->host(), 'sub.sub.example.com');

        $url->host('some.host.xyz');
        $this->assertEquals($url->host(), 'some.host.xyz');
        $this->assertEquals($url->subdomain(), 'some');
        $this->assertEquals($url->domain(), 'host.xyz');
        $this->assertEquals($url->domainLabel(), 'host');
        $this->assertEquals($url->domainSuffix(), 'xyz');
        $this->assertEquals(
            $url->toString(),
            'https://user:password@some.host.xyz:8080/some/path?some=query#fragment'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplaceSubdomain()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->subdomain(), 'sub.sub');

        $url->subdomain('www');
        $this->assertEquals($url->subdomain(), 'www');
        $this->assertEquals($url->host(), 'www.example.com');
        $this->assertEquals(
            $url->toString(),
            'https://user:password@www.example.com:8080/some/path?some=query#fragment'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplaceDomain()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->domain(), 'example.com');

        $url->domain('eggsample.wtf');
        $this->assertEquals($url->domain(), 'eggsample.wtf');
        $this->assertEquals($url->domainSuffix(), 'wtf');
        $this->assertEquals($url->domainLabel(), 'eggsample');
        $this->assertEquals($url->host(), 'sub.sub.eggsample.wtf');
        $this->assertEquals(
            $url->toString(),
            'https://user:password@sub.sub.eggsample.wtf:8080/some/path?some=query#fragment'
        );

        $url->domainLabel('xample');
        $this->assertEquals($url->domainLabel(), 'xample');
        $this->assertEquals($url->domain(), 'xample.wtf');
        $this->assertEquals($url->host(), 'sub.sub.xample.wtf');
        $this->assertEquals(
            $url->toString(),
            'https://user:password@sub.sub.xample.wtf:8080/some/path?some=query#fragment'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplaceDomainSuffix()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->domainSuffix(), 'com');

        $url->domainSuffix('org');
        $this->assertEquals($url->domainSuffix(), 'org');
        $this->assertEquals($url->domain(), 'example.org');
        $this->assertEquals($url->host(), 'sub.sub.example.org');
        $this->assertEquals(
            $url->toString(),
            'https://user:password@sub.sub.example.org:8080/some/path?some=query#fragment'
        );

        $url->domainSuffix('co.uk');
        $this->assertEquals($url->domainSuffix(), 'co.uk');
        $this->assertEquals($url->domain(), 'example.co.uk');
        $this->assertEquals($url->host(), 'sub.sub.example.co.uk');
        $this->assertEquals(
            $url->toString(),
            'https://user:password@sub.sub.example.co.uk:8080/some/path?some=query#fragment'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplacePort()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->port(), 8080);

        $url->port(123);
        $this->assertEquals($url->port(), 123);
        $this->assertEquals(
            $url->toString(),
            'https://user:password@sub.sub.example.com:123/some/path?some=query#fragment'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplacePath()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->path(), '/some/path');

        $url->path('/home');
        $this->assertEquals($url->path(), '/home');
        $this->assertEquals(
            $url->toString(),
            'https://user:password@sub.sub.example.com:8080/home?some=query#fragment'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplaceQueryString()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->query(), 'some=query');
        $this->assertEquals($url->queryArray(), ['some' => 'query']);

        $url->query('foo=bar');
        $this->assertEquals($url->query(), 'foo=bar');
        $this->assertEquals($url->queryArray(), ['foo' => 'bar']);
        $this->assertEquals(
            $url->toString(),
            'https://user:password@sub.sub.example.com:8080/some/path?foo=bar#fragment'
        );

        $url->queryArray(['a' => 'b', 'c' => 'd']);
        $this->assertEquals($url->query(), 'a=b&c=d');
        $this->assertEquals($url->queryArray(), ['a' => 'b', 'c' => 'd']);
        $this->assertEquals(
            $url->toString(),
            'https://user:password@sub.sub.example.com:8080/some/path?a=b&c=d#fragment'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplaceFragment()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals($url->fragment(), 'fragment');

        $url->fragment('test');
        $this->assertEquals($url->fragment(), 'test');
        $this->assertEquals(
            $url->toString(),
            'https://user:password@sub.sub.example.com:8080/some/path?some=query#test'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testReplaceComponentsWithUnexpectedDataTypes()
    {
        $url = $this->createDefaultUrlObject();

        $url->user(1234);
        $this->assertEquals($url->user(), '1234');

        $url->password(1234);
        $this->assertEquals($url->password(), '1234');

        $url->host(1234);
        $this->assertEquals($url->host(), '1234');
        $url->host('www.example.com');

        $url->domainLabel(1234);
        $this->assertEquals($url->domainLabel(), '1234');

        $url->subdomain(1234);
        $this->assertEquals($url->subdomain(), '1234');

        $url->port('8081');
        $this->assertEquals($url->port(), 8081);
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
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

        $this->assertInstanceOf(\Crwlr\Url\Url::class, $url);

        $this->assertEquals(
            $url->toString(),
            'http://john:god@www.crwlr.software:8081/foo/bar?key=value#anchor'
        );
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testResolveRelativeUrl()
    {
        $url = $this->createDefaultUrlObject();

        $this->assertEquals(
            $url->resolve('/different/path')->toString(),
            'https://user:password@sub.sub.example.com:8080/different/path'
        );

        // More tests on resolving relative to absolute urls => see ResolverTest.php
    }

    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
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

    public function testUriInterfaceMethods()
    {
        $url = \Crwlr\Url\Url::parse('http://www.example.com/foo/bar?query=string#fragment');

        $this->assertEquals($url->getScheme(), 'http');
        $url = $url->withScheme('https');
        $this->assertEquals($url->getScheme(), 'https');
        $url = $url->withScheme('');
        $this->assertEquals($url->getScheme(), '');
        $url = $url->withScheme('http');

        $this->assertEquals($url->getAuthority(), 'www.example.com');
        $url = $url->withHost('www.eggsample.com');
        $this->assertEquals($url->getAuthority(), 'www.eggsample.com');
        $url = $url->withHost('');
        $this->assertEquals($url->getAuthority(), '');
        $url = $url->withHost('www.example.com');

        $this->assertEquals($url->getUserInfo(), '');
        $url = $url->withUserInfo('otsch', 'crwlr');
        $this->assertEquals($url->getUserInfo(), 'otsch:crwlr');
        $this->assertEquals($url->getAuthority(), 'otsch:crwlr@www.example.com');
        $url = $url->withUserInfo('otsch', '');
        $this->assertEquals($url->getUserInfo(), 'otsch');
        $this->assertEquals($url->getAuthority(), 'otsch@www.example.com');
        $url = $url->withUserInfo('');
        $this->assertEquals($url->getUserInfo(), '');
        $this->assertEquals($url->getAuthority(), 'www.example.com');
        $this->assertNull($url->password()); // When the user is reset, password should also be.

        $this->assertEquals($url->getHost(), 'www.example.com');
        $url = $url->withHost('foo.bar.example.com');
        $this->assertEquals($url->getHost(), 'foo.bar.example.com');
        $url = $url->withHost('');
        $this->assertEquals($url->getHost(), '');
        $this->assertEquals($url->getAuthority(), '');

        $this->assertNull($url->getPort());
        $url = $url->withPort(1234);
        $this->assertEquals($url->getPort(), 1234);
        $url = $url->withPort(80);
        $this->assertNull($url->getPort()); // As 80 is standard http port it shouldn't be returned (see UriInterface)
        $url = $url->withPort(1234);

        // As the host is mandatory for an authority component, the getAuthority() method should not return ':1234'
        $this->assertEquals($url->getAuthority(), '');
        $url = $url->withUserInfo('einstein');
        $this->assertEquals($url->getAuthority(), '');
        $url = $url->withHost('www.example.com');
        $this->assertEquals($url->getAuthority(), 'einstein@www.example.com:1234');
        $url = $url->withUserInfo('einstein', 'albert');
        $this->assertEquals($url->getAuthority(), 'einstein:albert@www.example.com:1234');
        $url = $url->withPort(80);
        $this->assertEquals($url->getAuthority(), 'einstein:albert@www.example.com');
        $url = $url->withPort(null);
        $this->assertNull($url->getPort());

        $this->assertEquals($url->getPath(), '/foo/bar');
        $url = $url->withPath('baz');
        $this->assertEquals($url->getPath(), '/foo/baz');
        $url = $url->withPath('/bar/foo?baz=query#chapter3');
        $this->assertEquals($url->getPath(), '/bar/foo%3Fbaz=query%23chapter3');
        $url = $url->withPath('//foo/bar');
        $this->assertEquals($url->getPath(), '//foo/bar');

        $this->assertEquals($url->getQuery(), 'query=string');
        $url = $url->withQuery('key=value&key2=value2');
        $this->assertEquals($url->getQuery(), 'key=value&key2=value2');
        $url = $url->withQuery('');
        $this->assertEquals($url->getQuery(), '');
        $this->assertNull($url->query());

        $this->assertEquals($url->getFragment(), 'fragment');
        $url = $url->withFragment('differentfragment');
        $this->assertEquals($url->getFragment(), 'differentfragment');
        $url = $url->withFragment('');
        $this->assertEquals($url->getFragment(), '');
        $this->assertNull($url->fragment());
    }

    public function testGetStandardPortsByScheme()
    {
        $this->assertEquals(\Crwlr\Url\Url::getStandardPortByScheme('ftp'), 21);
        $this->assertEquals(\Crwlr\Url\Url::getStandardPortByScheme('git'), 9418);
        $this->assertEquals(\Crwlr\Url\Url::getStandardPortByScheme('http'), 80);
        $this->assertEquals(\Crwlr\Url\Url::getStandardPortByScheme('https'), 443);
        $this->assertEquals(\Crwlr\Url\Url::getStandardPortByScheme('imap'), 143);
        $this->assertEquals(\Crwlr\Url\Url::getStandardPortByScheme('irc'), 194);
        $this->assertEquals(\Crwlr\Url\Url::getStandardPortByScheme('nfs'), 2049);
        $this->assertEquals(\Crwlr\Url\Url::getStandardPortByScheme('rsync'), 873);
        $this->assertEquals(\Crwlr\Url\Url::getStandardPortByScheme('sftp'), 115);
        $this->assertEquals(\Crwlr\Url\Url::getStandardPortByScheme('smtp'), 25);

        $this->assertNull(\Crwlr\Url\Url::getStandardPortByScheme('unknownscheme'));
    }

    /**
     * @return \Crwlr\Url\Url
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    private function createDefaultUrlObject()
    {
        $url = new \Crwlr\Url\Url('https://user:password@sub.sub.example.com:8080/some/path?some=query#fragment');
        return $url;
    }
}
