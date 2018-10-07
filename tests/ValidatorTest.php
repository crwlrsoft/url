<?php
declare(strict_types=1);

use Crwlr\Url\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidateUrl()
    {
        $this->urlValidationResultContains(
            (new Validator())->url('https://www.crwlr.software/packages/url/v0.1.2#installation'),
            [
                'url' => 'https://www.crwlr.software/packages/url/v0.1.2#installation',
                'scheme' => 'https',
                'host' => 'www.crwlr.software',
                'path' => '/packages/url/v0.1.2',
                'fragment' => 'installation',
            ]
        );

        $this->urlValidationResultContains(
            (new Validator())->url('ftp://username:password@example.org'),
            [
                'url' => 'ftp://username:password@example.org',
                'scheme' => 'ftp',
                'user' => 'username',
                'pass' => 'password',
                'host' => 'example.org',
            ]
        );

        $this->urlValidationResultContains(
            (new Validator())->url('mailto:you@example.com?subject=crwlr software'),
            ['url' => 'mailto:you@example.com?subject=crwlr%20software']
        );
    }

    public function testValidateIdnUrl()
    {
        $this->urlValidationResultContains(
            (new Validator())->url('http://✪df.ws/123'),
            [
                'url' => 'http://xn--df-oiy.ws/123',
                'scheme' => 'http',
                'host' => 'xn--df-oiy.ws',
                'path' => '/123',
            ]
        );

        $this->urlValidationResultContains(
            (new Validator())->url('https://www.example.онлайн/stuff'),
            [
                'url' => 'https://www.example.xn--80asehdb/stuff',
                'scheme' => 'https',
                'host' => 'www.example.xn--80asehdb',
                'path' => '/stuff',
            ]
        );
    }

    /**
     * Invalid url strings.
     */
    public function testValidateInvalidUrl()
    {
        $this->assertNull((new Validator())->url('1http://example.com/stuff'));
        $this->assertNull((new Validator())->url('  https://wwww.example.com  '));
        $this->assertNull((new Validator())->url('http://'));
        $this->assertNull((new Validator())->url('http://.'));
        $this->assertNull((new Validator())->url('https://..'));
        $this->assertNull((new Validator())->url('https://../'));
        $this->assertNull((new Validator())->url('http://?'));
        $this->assertNull((new Validator())->url('http://#'));
        $this->assertNull((new Validator())->url('//'));
        $this->assertNull((new Validator())->url('///foo'));
        $this->assertNull((new Validator())->url('http:///foo'));
        $this->assertNull((new Validator())->url('://'));
    }

    public function testValidateScheme()
    {
        $validator = new Validator();

        $this->assertEquals($validator->scheme('http'), 'http');
        $this->assertEquals($validator->scheme('mailto'), 'mailto');
        $this->assertEquals($validator->scheme('ssh'), 'ssh');
        $this->assertEquals($validator->scheme('ftp'), 'ftp');
        $this->assertEquals($validator->scheme('sftp'), 'sftp');
        $this->assertEquals($validator->scheme('wss'), 'wss');
        $this->assertEquals($validator->scheme('HTTPS'), 'https');

        $this->assertFalse($validator->scheme('1invalidscheme'));
        $this->assertFalse($validator->scheme('mäilto'));
    }

    public function testValidateUserOrPassword()
    {
        $validator = new Validator();

        $this->assertEquals($validator->userOrPassword('user'), 'user');
        $this->assertEquals($validator->userOrPassword('pASS123'), 'pASS123');
        $this->assertEquals($validator->userOrPassword('user-123'), 'user-123');
        $this->assertEquals($validator->userOrPassword('P4ss.123'), 'P4ss.123');
        $this->assertEquals($validator->userOrPassword('user_123'), 'user_123');
        $this->assertEquals($validator->userOrPassword('p4ss~123'), 'p4ss~123');
        $this->assertEquals($validator->userOrPassword('user%123'), 'user%123');
        $this->assertEquals($validator->userOrPassword('p4ss-123!'), 'p4ss-123!');
        $this->assertEquals($validator->userOrPassword('u$3r_n4m3!'), 'u$3r_n4m3!');
        $this->assertEquals($validator->userOrPassword('p4$$&w0rD'), 'p4$$&w0rD');
        $this->assertEquals($validator->userOrPassword('u$3r\'$_n4m3'), 'u$3r\'$_n4m3');
        $this->assertEquals($validator->userOrPassword('(p4$$-w0rD)'), '(p4$$-w0rD)');
        $this->assertEquals($validator->userOrPassword('u$3r*n4m3'), 'u$3r*n4m3');
        $this->assertEquals($validator->userOrPassword('p4$$+W0rD'), 'p4$$+W0rD');
        $this->assertEquals($validator->userOrPassword('u$3r,n4m3'), 'u$3r,n4m3');
        $this->assertEquals($validator->userOrPassword('P4ss;w0rd'), 'P4ss;w0rd');
        $this->assertEquals($validator->userOrPassword('=u$3r='), '=u$3r=');

        $this->assertFalse($validator->userOrPassword('u§3rname'));
        $this->assertFalse($validator->userOrPassword('"password"'));
        $this->assertFalse($validator->userOrPassword('user:name'));
        $this->assertFalse($validator->userOrPassword('pass`word'));
        $this->assertFalse($validator->userOrPassword('Üsernäme'));
        $this->assertFalse($validator->userOrPassword('pass^word'));
        $this->assertFalse($validator->userOrPassword('user°name'));
        $this->assertFalse($validator->userOrPassword('pass🤓moji'));
        $this->assertFalse($validator->userOrPassword('<username>'));
        $this->assertFalse($validator->userOrPassword('pass\word'));
        $this->assertFalse($validator->userOrPassword('usern@me'));
        $this->assertFalse($validator->userOrPassword('paßword'));
        $this->assertFalse($validator->userOrPassword('us€rname'));
    }

    public function testValidateHost()
    {
        $validator = new Validator();

        $this->assertEquals($validator->host('example.com'), 'example.com');
        $this->assertEquals($validator->host('www.example.com'), 'www.example.com');
        $this->assertEquals($validator->host('subdomain.example.com'), 'subdomain.example.com');
        $this->assertEquals($validator->host('www.some-domain.io'), 'www.some-domain.io');
        $this->assertEquals($validator->host('123456.co.uk'), '123456.co.uk');
        $this->assertEquals($validator->host('WWW.EXAMPLE.COM'), 'WWW.EXAMPLE.COM');
        $this->assertEquals($validator->host('www-something.blog'), 'www-something.blog');
        $this->assertEquals($validator->host('h4ck0r.software'), 'h4ck0r.software');
        $this->assertEquals($validator->host('g33ks.org'), 'g33ks.org');
        $this->assertEquals($validator->host('example.xn--80asehdb'), 'example.xn--80asehdb');
        $this->assertEquals($validator->host('example.онлайн'), 'example.xn--80asehdb');
        $this->assertEquals($validator->host('12.34.56.78'), '12.34.56.78');
        $this->assertEquals($validator->host('localhost'), 'localhost');
        $this->assertEquals($validator->host('dev.local'), 'dev.local');

        $this->assertFalse($validator->host('slash/example.com'));
        $this->assertFalse($validator->host('exclamation!mark.co'));
        $this->assertFalse($validator->host('question?mark.blog'));
        $this->assertFalse($validator->host('under_score.org'));
        $this->assertFalse($validator->host('www.(parenthesis).net'));
        $this->assertFalse($validator->host('idk.amper&sand.uk'));
        $this->assertFalse($validator->host('per%cent.de'));
        $this->assertFalse($validator->host('equals=.ch'));
        $this->assertFalse($validator->host('apostrophe\'.at'));
        $this->assertFalse($validator->host('one+one.mobile'));
        $this->assertFalse($validator->host('hash#tag.social'));
        $this->assertFalse($validator->host('co:lon.com'));
        $this->assertFalse($validator->host('semi;colon.net'));
        $this->assertFalse($validator->host('<html>.codes'));
    }

    public function testValidateDomainSuffix()
    {
        $validator = new Validator();

        $this->assertEquals($validator->domainSuffix('com'), 'com');
        $this->assertEquals($validator->domainSuffix('org'), 'org');
        $this->assertEquals($validator->domainSuffix('net'), 'net');
        $this->assertEquals($validator->domainSuffix('blog'), 'blog');
        $this->assertEquals($validator->domainSuffix('codes'), 'codes');
        $this->assertEquals($validator->domainSuffix('wtf'), 'wtf');
        $this->assertEquals($validator->domainSuffix('sexy'), 'sexy');
        $this->assertEquals($validator->domainSuffix('tennis'), 'tennis');
        $this->assertEquals($validator->domainSuffix('versicherung'), 'versicherung');
        $this->assertEquals($validator->domainSuffix('点看'), 'xn--3pxu8k');
        $this->assertEquals($validator->domainSuffix('онлайн'), 'xn--80asehdb');
        $this->assertEquals($validator->domainSuffix('大拿'), 'xn--pssy2u');
        $this->assertEquals($validator->domainSuffix('co.uk'), 'co.uk');
        $this->assertEquals($validator->domainSuffix('co.at'), 'co.at');
        $this->assertEquals($validator->domainSuffix('or.at'), 'or.at');
        $this->assertEquals($validator->domainSuffix('anything.bd'), 'anything.bd');

        $this->assertFalse($validator->domainSuffix('süffix'));
        $this->assertFalse($validator->domainSuffix('idk'));
    }

    public function testValidateDomain()
    {
        $validator = new Validator();

        $this->assertEquals($validator->domain('google.com'), 'google.com');
        $this->assertEquals($validator->domain('example.xn--80asehdb'), 'example.xn--80asehdb');
        $this->assertEquals($validator->domain('example.онлайн'), 'example.xn--80asehdb');
        $this->assertEquals($validator->domain('yolo', true), 'yolo');

        $this->assertFalse($validator->domain('www.google.com'));
        $this->assertFalse($validator->domain('yolo'));
        $this->assertFalse($validator->domain('subdomain.example.онлайн'));
    }

    public function testValidateSubdomain()
    {
        $validator = new Validator();

        $this->assertEquals($validator->subdomain('www'), 'www');
        $this->assertEquals($validator->subdomain('sub.domain'), 'sub.domain');
        $this->assertEquals($validator->subdomain('SUB.DO.MAIN'), 'sub.do.main');

        $this->assertFalse($validator->subdomain('sub_domain'));
    }

    public function testValidatePort()
    {
        $validator = new Validator();

        $this->assertEquals($validator->port(0), 0);
        $this->assertEquals($validator->port('0'), 0);
        $this->assertEquals($validator->port(8080), 8080);
        $this->assertEquals($validator->port('8080'), 8080);
        $this->assertEquals($validator->port(65535), 65535);
        $this->assertEquals($validator->port('65535'), 65535);

        $this->assertFalse($validator->port(-1));
        $this->assertFalse($validator->port('invalid'));
        $this->assertFalse($validator->port(65536));
    }

    public function testValidatePath()
    {
        $validator = new Validator();

        $this->assertEquals($validator->path('/FoO/bAr'), '/FoO/bAr');
        $this->assertEquals($validator->path('/foo-123/bar_456'), '/foo-123/bar_456');
        $this->assertEquals($validator->path('/~foo/!bar$/&baz\''), '/~foo/!bar$/&baz\'');
        $this->assertEquals($validator->path('/(foo)/*bar+'), '/(foo)/*bar+');
        $this->assertEquals($validator->path('/foo,bar;baz:'), '/foo,bar;baz:');
        $this->assertEquals($validator->path('/foo=bar@baz'), '/foo=bar@baz');
        $this->assertEquals($validator->path('/foo%bar'), '/foo%25bar');
        $this->assertEquals($validator->path('/"foo"'), '/%22foo%22');
        $this->assertEquals($validator->path('/foo\\bar'), '/foo%5Cbar');
        $this->assertEquals($validator->path('/bößer/pfad'), '/b%C3%B6%C3%9Fer/pfad');
        $this->assertEquals($validator->path('/<html>'), '/%3Chtml%3E');

        // By default the path validation method assumes the uri where the path is contained contains an authority
        // component. According to RFC 3986 (3.3. Path) a uri that contains an authority must be empty or begin with a
        // slash.
        $this->assertFalse($validator->path('no/leading/slash'));

        // If the uri that contains the given path component has no authority component you can set the $hasAuthority
        // parameter to false and it should work with a relative path that does not begin with slash.
        $this->assertEquals('no/leading/slash', $validator->path('no/leading/slash', false));
    }

    public function testValidateQuery()
    {
        $validator = new Validator();

        $this->assertEquals($validator->query('foo=bar'), 'foo=bar');
        $this->assertEquals($validator->query('?foo=bar'), 'foo=bar');
        $this->assertEquals($validator->query('foo1=bar&foo2=baz'), 'foo1=bar&foo2=baz');
        $this->assertEquals($validator->query('.foo-=_bar~'), '.foo-=_bar~');
        $this->assertEquals($validator->query('%foo!=$bar\''), '%25foo!=$bar\'');
        $this->assertEquals($validator->query('(foo)=*bar+'), '(foo)=*bar+');
        $this->assertEquals($validator->query('f,o;o==bar:'), 'f,o;o==bar:');
        $this->assertEquals($validator->query('?@foo=/bar?'), '@foo=/bar%3F');
        $this->assertEquals($validator->query('"foo"=bar'), '%22foo%22=bar');
        $this->assertEquals($validator->query('foo#=bar'), 'foo%23=bar');
        $this->assertEquals($validator->query('föo=bar'), 'f%C3%B6o=bar');
        $this->assertEquals($validator->query('boeßer=query'), 'boe%C3%9Fer=query');
        $this->assertEquals($validator->query('foo`=bar'), 'foo%60=bar');
    }

    public function testValidateFragment()
    {
        $validator = new Validator();

        $this->assertEquals($validator->fragment('fragment'), 'fragment');
        $this->assertEquals($validator->fragment('#fragment'), 'fragment');
        $this->assertEquals($validator->fragment('fragment1234567890'), 'fragment1234567890');
        $this->assertEquals($validator->fragment('-.fragment_~'), '-.fragment_~');
        $this->assertEquals($validator->fragment('%!fragment$&'), '%25!fragment$&');
        $this->assertEquals($validator->fragment('(\'fragment*)'), '(\'fragment*)');
        $this->assertEquals($validator->fragment('#+,fragment;:'), '+,fragment;:');
        $this->assertEquals($validator->fragment('@=fragment/?'), '@=fragment/?');
        $this->assertEquals($validator->fragment('#"fragment"'), '%22fragment%22');
        $this->assertEquals($validator->fragment('#fragment#'), 'fragment%23');
        $this->assertEquals($validator->fragment('##fragment'), '%23fragment');
        $this->assertEquals($validator->fragment('frägment'), 'fr%C3%A4gment');
        $this->assertEquals($validator->fragment('boeßesfragment'), 'boe%C3%9Fesfragment');
        $this->assertEquals($validator->fragment('fragment`'), 'fragment%60');
    }

    /**
     * @param $validationResult
     * @param array $contains
     */
    private function urlValidationResultContains($validationResult, array $contains)
    {
        $this->assertInternalType('array', $validationResult);

        foreach ($contains as $key => $value) {
            $this->assertArrayHasKey($key, $validationResult);
            $this->assertEquals($value, $validationResult[$key]);
        }
    }
}
