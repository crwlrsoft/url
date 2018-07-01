<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testValidateUrl()
    {
        $validator = new \Crwlr\Url\Validator();

        $this->assertEquals(
            $validator->url('https://www.crwlr.software'),
            'https://www.crwlr.software'
        );
        $this->assertEquals(
            $validator->url('mailto:you@example.com?subject=crwlr software'),
            'mailto:you@example.com?subject=crwlr%20software'
        );
        $this->assertEquals(
            $validator->url('  https://wwww.example.com  '),
            'https://wwww.example.com'
        );
        $this->assertEquals(
            $validator->url('ssh://username@host:/path/to/somewhere'),
            'ssh://username@host:/path/to/somewhere'
        );
        $this->assertEquals(
            $validator->url('ftp://username:password@example.org'),
            'ftp://username:password@example.org'
        );
        $this->assertEquals(
            $validator->url('http://www.example.онлайн/stuff'),
            'http://www.example.xn--80asehdb/stuff'
        );

        $invalidUrls = [
            null,
            'this is not an url',
            '1http://example.com/stuff',
            'mäilto:user@example.com',
            '/foo/bar',
        ];

        foreach ($invalidUrls as $url) {
            $this->expectException(\Crwlr\Url\Exceptions\InvalidUrlException::class);
            $validator->url($url);
        }
    }

    public function testValidateScheme()
    {
        $validator = new \Crwlr\Url\Validator();

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
        $validator = new \Crwlr\Url\Validator();

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
        $validator = new \Crwlr\Url\Validator();

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
        $validator = new \Crwlr\Url\Validator();

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
        $validator = new \Crwlr\Url\Validator();

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
        $validator = new \Crwlr\Url\Validator();

        $this->assertEquals($validator->subdomain('www'), 'www');
        $this->assertEquals($validator->subdomain('sub.domain'), 'sub.domain');
        $this->assertEquals($validator->subdomain('SUB.DO.MAIN'), 'sub.do.main');

        $this->assertFalse($validator->subdomain('sub_domain'));
    }

    public function testValidatePort()
    {
        $validator = new \Crwlr\Url\Validator();

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
        $validator = new \Crwlr\Url\Validator();

        $this->assertEquals($validator->path('/FoO/bAr'), '/FoO/bAr');
        $this->assertEquals($validator->path('/foo-123/bar_456'), '/foo-123/bar_456');
        $this->assertEquals($validator->path('/~foo/!bar$/&baz\''), '/~foo/!bar$/&baz\'');
        $this->assertEquals($validator->path('/(foo)/*bar+'), '/(foo)/*bar+');
        $this->assertEquals($validator->path('/foo,bar;baz:'), '/foo,bar;baz:');
        $this->assertEquals($validator->path('/foo=bar@baz'), '/foo=bar@baz');
        $this->assertEquals($validator->path('/foo%bar'), '/foo%25bar');
        $this->assertEquals($validator->path('no/leading/slash'), 'no/leading/slash');
        $this->assertEquals($validator->path('/"foo"'), '/%22foo%22');
        $this->assertEquals($validator->path('/foo\\bar'), '/foo%5Cbar');
        $this->assertEquals($validator->path('/bößer/pfad'), '/b%C3%B6%C3%9Fer/pfad');
        $this->assertEquals($validator->path('/<html>'), '/%3Chtml%3E');
    }

    public function testValidateQuery()
    {
        $validator = new \Crwlr\Url\Validator();

        $this->assertEquals($validator->query('foo=bar'), 'foo=bar');
        $this->assertEquals($validator->query('?foo=bar'), 'foo=bar');
        $this->assertEquals($validator->query('foo1=bar&foo2=baz'), 'foo1=bar&foo2=baz');
        $this->assertEquals($validator->query('.foo-=_bar~'), '.foo-=_bar~');
        $this->assertEquals($validator->query('%foo!=$bar\''), '%foo!=$bar\'');
        $this->assertEquals($validator->query('(foo)=*bar+'), '(foo)=*bar+');
        $this->assertEquals($validator->query('f,o;o==bar:'), 'f,o;o==bar:');
        $this->assertEquals($validator->query('?@foo=/bar?'), '@foo=/bar?');

        $this->assertFalse($validator->query('"foo"=bar'));
        $this->assertFalse($validator->query('foo#=bar'));
        $this->assertFalse($validator->query('föo=bar'));
        $this->assertFalse($validator->query('boeßer=query'));
        $this->assertFalse($validator->query('foo`=bar'));
    }

    public function testValidateFragment()
    {
        $validator = new \Crwlr\Url\Validator();

        $this->assertEquals($validator->fragment('fragment'), 'fragment');
        $this->assertEquals($validator->fragment('#fragment'), 'fragment');
        $this->assertEquals($validator->fragment('fragment1234567890'), 'fragment1234567890');
        $this->assertEquals($validator->fragment('-.fragment_~'), '-.fragment_~');
        $this->assertEquals($validator->fragment('%!fragment$&'), '%!fragment$&');
        $this->assertEquals($validator->fragment('(\'fragment*)'), '(\'fragment*)');
        $this->assertEquals($validator->fragment('#+,fragment;:'), '+,fragment;:');
        $this->assertEquals($validator->fragment('@=fragment/?'), '@=fragment/?');

        $this->assertFalse($validator->fragment('#"fragment"'));
        $this->assertFalse($validator->fragment('#fragment#'));
        $this->assertFalse($validator->fragment('##fragment'));
        $this->assertFalse($validator->fragment('frägment'));
        $this->assertFalse($validator->fragment('boeßesfragment'));
        $this->assertFalse($validator->fragment('fragment`'));
    }
}
