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

        $this->assertTrue($url->isEqualTo($equalUrl->__toString()));
        $notEqualUrl = new Url('https://not.equal.url/totally/different');
        $this->assertFalse($url->isEqualTo($notEqualUrl->__toString()));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'scheme'));
        $this->assertTrue($url->isSchemeEqualIn($equalUrl));
        $equalUrl->scheme('http');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'scheme'));
        $this->assertFalse($url->isSchemeEqualIn($equalUrl));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'user'));
        $this->assertTrue($url->isUserEqualIn($equalUrl));
        $equalUrl->user('usher');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'user'));
        $this->assertFalse($url->isUserEqualIn($equalUrl));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'pass'));
        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'password'));
        $this->assertTrue($url->isPasswordEqualIn($equalUrl));
        $equalUrl->pass('pass');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'pass'));
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'password'));
        $this->assertFalse($url->isPasswordEqualIn($equalUrl));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'host'));
        $this->assertTrue($url->isHostEqualIn($equalUrl));
        $equalUrl->host('www.example.com');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'host'));
        $this->assertFalse($url->isHostEqualIn($equalUrl));
        $equalUrl->host('sub.sub.example.com');

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domain'));
        $this->assertTrue($url->isDomainEqualIn($equalUrl));
        $equalUrl->domain('eggsample.com');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domain'));
        $this->assertFalse($url->isDomainEqualIn($equalUrl));
        $equalUrl->domain('example.com');

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domainLabel'));
        $this->assertTrue($url->isDomainLabelEqualIn($equalUrl));
        $equalUrl->domainLabel('eggsample');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domainLabel'));
        $this->assertFalse($url->isDomainLabelEqualIn($equalUrl));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domainSuffix'));
        $this->assertTrue($url->isDomainSuffixEqualIn($equalUrl));
        $equalUrl->domainSuffix('org');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domainSuffix'));
        $this->assertFalse($url->isDomainSuffixEqualIn($equalUrl));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'subdomain'));
        $this->assertTrue($url->isSubdomainEqualIn($equalUrl));
        $equalUrl->subdomain('www');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'subdomain'));
        $this->assertFalse($url->isSubdomainEqualIn($equalUrl));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'port'));
        $this->assertTrue($url->isPortEqualIn($equalUrl));
        $equalUrl->port(123);
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'port'));
        $this->assertFalse($url->isPortEqualIn($equalUrl));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'path'));
        $this->assertTrue($url->isPathEqualIn($equalUrl));
        $equalUrl->path('/different/path');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'path'));
        $this->assertFalse($url->isPathEqualIn($equalUrl));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'query'));
        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'queryArray'));
        $this->assertTrue($url->isQueryEqualIn($equalUrl));
        $equalUrl->query('foo=bar');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'query'));
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'queryArray'));
        $this->assertFalse($url->isQueryEqualIn($equalUrl));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'fragment'));
        $this->assertTrue($url->isFragmentEqualIn($equalUrl));
        $equalUrl->fragment('foo');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'fragment'));
        $this->assertFalse($url->isFragmentEqualIn($equalUrl));

        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'root'));
        $equalUrl->host('www.foo.org');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'root'));

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'relative'));
        $equalUrl->path('/different/path');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'relative'));
    }

    /**
     * Special characters in user information will be percent encoded.
     *
     * @throws InvalidUrlException
     */
    public function testUrlWithSpecialCharactersInUserInfo()
    {
        $url = Url::parse('https://u§er:pássword@example.com');
        $this->assertEquals('https://u%C2%A7er:p%C3%A1ssword@example.com', $url->toString());
    }

    /**
     * Parsing urls containing special characters like umlauts in path, query or fragment percent encodes these
     * characters.
     *
     * @throws InvalidUrlException
     */
    public function testParsingUrlsContainingUmlauts()
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
     *
     * @throws InvalidUrlException
     */
    public function testEncodingPercentEncodedCharacters()
    {
        $url = Url::parse('https://www.example.com/b%C3%BCrokaufmann');
        $this->assertEquals('https://www.example.com/b%C3%BCrokaufmann', $url->toString());
        $url = Url::parse('https://www.example.com/just%-character');
        $this->assertEquals('https://www.example.com/just%25-character', $url->toString());
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
