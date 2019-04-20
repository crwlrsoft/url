<?php
declare(strict_types=1);

use Crwlr\Url\Exceptions\InvalidUrlException;
use Crwlr\Url\Url;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
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
        new Url('https://');
    }

    public function testCanBeCreatedFromRelativeUrl()
    {
        $url = new Url('/foo/bar?query=string');
        $this->assertInstanceOf(Url::class, $url);
    }

    public function testCantBeCreatedFromRelativePath()
    {
        $url = new Url('yo/lo');
        $this->assertInstanceOf(Url::class, $url);
    }

    public function testCanBeCreatedViaFactoryMethod()
    {
        $url = Url::parse('http://www.example.com');
        $this->assertInstanceOf(Url::class, $url);
    }

    public function testParseUrl()
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

    public function testClassPropertyAccess()
    {
        $url = $this->createDefaultUrlObject();
        $this->assertEquals('https', $url->scheme);
        $this->assertEquals('user:password@sub.sub.example.com:8080', $url->authority);
        $this->assertEquals('user', $url->user);
        $this->assertEquals('password', $url->password);
        $this->assertEquals('password', $url->pass);
        $this->assertEquals('user:password', $url->userInfo);
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
    public function testUrlWithInvalidHost()
    {
        $this->expectException(InvalidUrlException::class);
        Url::parse('https://www.exclamation!mark.co');
    }

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

    public function testReplaceAuthority()
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

    public function testReplaceUserInfo()
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

    public function testIsRelativeReference()
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

    public function testResolveRelativeReference()
    {
        $url = $this->createDefaultUrlObject();

        $this->assertEquals(
            'https://user:password@sub.sub.example.com:8080/different/path',
            $url->resolve('/different/path')->toString()
        );

        // More tests on resolving relative to absolute urls => see ResolverTest.php
    }

    public function testCompareUrls()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isEqualTo($equalUrl->__toString()));
        $equalUrl->port(1);
        $this->assertFalse($url->isEqualTo($equalUrl->__toString()));
    }

    public function testCompareScheme()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'scheme'));
        $this->assertTrue($url->isSchemeEqualIn($equalUrl));
        $equalUrl->scheme('http');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'scheme'));
        $this->assertFalse($url->isSchemeEqualIn($equalUrl));
    }

    public function testCompareAuthority()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'authority'));
        $this->assertTrue($url->isAuthorityEqualIn($equalUrl));
        $equalUrl->authority('sub.sub.example.com');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'authority'));
        $this->assertFalse($url->isAuthorityEqualIn($equalUrl));
    }

    public function testCompareUser()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'user'));
        $this->assertTrue($url->isUserEqualIn($equalUrl));
        $equalUrl->user('usher');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'user'));
        $this->assertFalse($url->isUserEqualIn($equalUrl));
    }

    public function testComparePassword()
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

    public function testCompareUserInfo()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'userInfo'));
        $this->assertTrue($url->isUserInfoEqualIn($equalUrl));
        $equalUrl->userInfo('u§3r:p455w0rd');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'userInfo'));
        $this->assertFalse($url->isUserInfoEqualIn($equalUrl));
    }

    public function testCompareHost()
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

    public function testCompareDomain()
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

    public function testCompareDomainLabel()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domainLabel'));
        $this->assertTrue($url->isDomainLabelEqualIn($equalUrl));
        $equalUrl->domainLabel('eggsample');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domainLabel'));
        $this->assertFalse($url->isDomainLabelEqualIn($equalUrl));
    }

    public function testCompareDomainSuffix()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'domainSuffix'));
        $this->assertTrue($url->isDomainSuffixEqualIn($equalUrl));
        $equalUrl->domainSuffix('org');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'domainSuffix'));
        $this->assertFalse($url->isDomainSuffixEqualIn($equalUrl));
    }

    public function testCompareSubdomain()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'subdomain'));
        $this->assertTrue($url->isSubdomainEqualIn($equalUrl));
        $equalUrl->subdomain('www');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'subdomain'));
        $this->assertFalse($url->isSubdomainEqualIn($equalUrl));
    }

    public function testComparePort()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'port'));
        $this->assertTrue($url->isPortEqualIn($equalUrl));
        $equalUrl->port(123);
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'port'));
        $this->assertFalse($url->isPortEqualIn($equalUrl));
    }

    public function testComparePath()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'path'));
        $this->assertTrue($url->isPathEqualIn($equalUrl));
        $equalUrl->path('/different/path');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'path'));
        $this->assertFalse($url->isPathEqualIn($equalUrl));
    }

    public function testCompareQuery()
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

    public function testCompareFragment()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'fragment'));
        $this->assertTrue($url->isFragmentEqualIn($equalUrl));
        $equalUrl->fragment('foo');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'fragment'));
        $this->assertFalse($url->isFragmentEqualIn($equalUrl));
    }

    public function testCompareRoot()
    {
        $url = $this->createDefaultUrlObject();
        $equalUrl = $this->createDefaultUrlObject();

        $this->assertTrue($url->isComponentEqualIn($equalUrl, 'root'));
        $equalUrl->host('www.foo.org');
        $this->assertFalse($url->isComponentEqualIn($equalUrl, 'root'));
    }

    public function testCompareRelative()
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
    public function testUrlWithSpecialCharactersInUserInfo()
    {
        $url = Url::parse('https://u§er:pássword@example.com');
        $this->assertEquals('https://u%C2%A7er:p%C3%A1ssword@example.com', $url->toString());
    }

    /**
     * Parsing urls containing special characters like umlauts in path, query or fragment percent encodes these
     * characters.
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
     */
    public function testEncodingPercentEncodedCharacters()
    {
        $url = Url::parse('https://www.example.com/b%C3%BCrokaufmann');
        $this->assertEquals('https://www.example.com/b%C3%BCrokaufmann', $url->toString());
        $url = Url::parse('https://www.example.com/just%-character');
        $this->assertEquals('https://www.example.com/just%25-character', $url->toString());
    }

    public function testHasIdn()
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
     */
    public function testCreateRelativePathReferenceWithAuthority()
    {
        $url = Url::parse('relative/reference');
        $url->scheme('https');

        $this->expectException(InvalidUrlException::class);
        $url->host('www.example.com');
    }

    public function testEncodingEdgeCases()
    {
        $url = Url::parse('https://u§er:pássword@ком.香格里拉.電訊盈科:1234/föô/bár bàz?quär.y=strïng#frägmänt');
        $this->assertEquals(
            'https://u%C2%A7er:p%C3%A1ssword@xn--j1aef.xn--5su34j936bgsg.xn--fzys8d69uvgm:1234/f%C3%B6%C3%B4/' .
            'b%C3%A1r%20b%C3%A0z?qu%C3%A4r.y=str%C3%AFng#fr%C3%A4gm%C3%A4nt',
            $url->__toString()
        );
    }

    /**
     * @return Url
     */
    private function createDefaultUrlObject()
    {
        $url = new Url('https://user:password@sub.sub.example.com:8080/some/path?some=query#fragment');
        return $url;
    }
}
