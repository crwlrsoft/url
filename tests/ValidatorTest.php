<?php
declare(strict_types=1);

use Crwlr\Url\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidateUrl()
    {
        $this->urlValidationResultContains(
            Validator::url('https://www.crwlr.software/packages/url/v0.1.2#installation'),
            [
                'url' => 'https://www.crwlr.software/packages/url/v0.1.2#installation',
                'scheme' => 'https',
                'host' => 'www.crwlr.software',
                'path' => '/packages/url/v0.1.2',
                'fragment' => 'installation',
            ]
        );

        $this->urlValidationResultContains(
            Validator::url('ftp://username:password@example.org'),
            [
                'url' => 'ftp://username:password@example.org',
                'scheme' => 'ftp',
                'user' => 'username',
                'pass' => 'password',
                'host' => 'example.org',
            ]
        );

        $this->urlValidationResultContains(
            Validator::url('mailto:you@example.com?subject=crwlr software'),
            ['url' => 'mailto:you@example.com?subject=crwlr%20software']
        );
    }

    public function testValidateIdnUrl()
    {
        $this->urlValidationResultContains(
            Validator::url('http://✪df.ws/123'),
            [
                'url' => 'http://xn--df-oiy.ws/123',
                'scheme' => 'http',
                'host' => 'xn--df-oiy.ws',
                'path' => '/123',
            ]
        );

        $this->urlValidationResultContains(
            Validator::url('https://www.example.онлайн/stuff'),
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
        $this->assertNull(Validator::url('1http://example.com/stuff'));
        $this->assertNull(Validator::url('  https://wwww.example.com  '));
        $this->assertNull(Validator::url('http://'));
        $this->assertNull(Validator::url('http://.'));
        $this->assertNull(Validator::url('https://..'));
        $this->assertNull(Validator::url('https://../'));
        $this->assertNull(Validator::url('http://?'));
        $this->assertNull(Validator::url('http://#'));
        $this->assertNull(Validator::url('//'));
        $this->assertNull(Validator::url('///foo'));
        $this->assertNull(Validator::url('http:///foo'));
        $this->assertNull(Validator::url('://'));
    }

    public function testValidateScheme()
    {
        $this->assertEquals('http', Validator::scheme('http'));
        $this->assertEquals('mailto', Validator::scheme('mailto'));
        $this->assertEquals('ssh', Validator::scheme('ssh'));
        $this->assertEquals('ftp', Validator::scheme('ftp'));
        $this->assertEquals('sftp', Validator::scheme('sftp'));
        $this->assertEquals('wss', Validator::scheme('wss'));
        $this->assertEquals('https', Validator::scheme('HTTPS'));

        $this->assertNull(Validator::scheme('1invalidscheme'));
        $this->assertNull(Validator::scheme('mäilto'));
    }

    public function testValidateUserOrPassword()
    {
        $this->assertEquals('user', Validator::userOrPassword('user'));
        $this->assertEquals('pASS123', Validator::userOrPassword('pASS123'));
        $this->assertEquals('user-123', Validator::userOrPassword('user-123'));
        $this->assertEquals('P4ss.123', Validator::userOrPassword('P4ss.123'));
        $this->assertEquals('user_123', Validator::userOrPassword('user_123'));
        $this->assertEquals('p4ss~123', Validator::userOrPassword('p4ss~123'));
        $this->assertEquals('user%123', Validator::userOrPassword('user%123'));
        $this->assertEquals('p4ss-123!', Validator::userOrPassword('p4ss-123!'));
        $this->assertEquals('u$3r_n4m3!', Validator::userOrPassword('u$3r_n4m3!'));
        $this->assertEquals('p4$$&w0rD', Validator::userOrPassword('p4$$&w0rD'));
        $this->assertEquals('u$3r\'$_n4m3', Validator::userOrPassword('u$3r\'$_n4m3'));
        $this->assertEquals('(p4$$-w0rD)', Validator::userOrPassword('(p4$$-w0rD)'));
        $this->assertEquals('u$3r*n4m3', Validator::userOrPassword('u$3r*n4m3'));
        $this->assertEquals('p4$$+W0rD', Validator::userOrPassword('p4$$+W0rD'));
        $this->assertEquals('u$3r,n4m3', Validator::userOrPassword('u$3r,n4m3'));
        $this->assertEquals('P4ss;w0rd', Validator::userOrPassword('P4ss;w0rd'));
        $this->assertEquals('=u$3r=', Validator::userOrPassword('=u$3r='));

        $this->assertNull(Validator::userOrPassword('u§3rname'));
        $this->assertNull(Validator::userOrPassword('"password"'));
        $this->assertNull(Validator::userOrPassword('user:name'));
        $this->assertNull(Validator::userOrPassword('pass`word'));
        $this->assertNull(Validator::userOrPassword('Üsernäme'));
        $this->assertNull(Validator::userOrPassword('pass^word'));
        $this->assertNull(Validator::userOrPassword('user°name'));
        $this->assertNull(Validator::userOrPassword('pass🤓moji'));
        $this->assertNull(Validator::userOrPassword('<username>'));
        $this->assertNull(Validator::userOrPassword('pass\word'));
        $this->assertNull(Validator::userOrPassword('usern@me'));
        $this->assertNull(Validator::userOrPassword('paßword'));
        $this->assertNull(Validator::userOrPassword('us€rname'));
    }

    public function testValidateHost()
    {
        $this->assertEquals('example.com', Validator::host('example.com'));
        $this->assertEquals('www.example.com', Validator::host('www.example.com'));
        $this->assertEquals('subdomain.example.com', Validator::host('subdomain.example.com'));
        $this->assertEquals('www.some-domain.io', Validator::host('www.some-domain.io'));
        $this->assertEquals('123456.co.uk', Validator::host('123456.co.uk'));
        $this->assertEquals('WWW.EXAMPLE.COM', Validator::host('WWW.EXAMPLE.COM'));
        $this->assertEquals('www-something.blog', Validator::host('www-something.blog'));
        $this->assertEquals('h4ck0r.software', Validator::host('h4ck0r.software'));
        $this->assertEquals('g33ks.org', Validator::host('g33ks.org'));
        $this->assertEquals('example.xn--80asehdb', Validator::host('example.онлайн'));
        $this->assertEquals('example.xn--80asehdb', Validator::host('example.xn--80asehdb'));
        $this->assertEquals('www.xn--80a7a.com', Validator::host('www.са.com')); // Fake "a" in ca.com => idn domain
        $this->assertEquals('12.34.56.78', Validator::host('12.34.56.78'));
        $this->assertEquals('localhost', Validator::host('localhost'));
        $this->assertEquals('dev.local', Validator::host('dev.local'));

        $this->assertNull(Validator::host('slash/example.com'));
        $this->assertNull(Validator::host('exclamation!mark.co'));
        $this->assertNull(Validator::host('question?mark.blog'));
        $this->assertNull(Validator::host('under_score.org'));
        $this->assertNull(Validator::host('www.(parenthesis).net'));
        $this->assertNull(Validator::host('idk.amper&sand.uk'));
        $this->assertNull(Validator::host('per%cent.de'));
        $this->assertNull(Validator::host('equals=.ch'));
        $this->assertNull(Validator::host('apostrophe\'.at'));
        $this->assertNull(Validator::host('one+one.mobile'));
        $this->assertNull(Validator::host('hash#tag.social'));
        $this->assertNull(Validator::host('co:lon.com'));
        $this->assertNull(Validator::host('semi;colon.net'));
        $this->assertNull(Validator::host('<html>.codes'));
    }

    public function testValidateDomainSuffix()
    {
        $this->assertEquals('com', Validator::domainSuffix('com'));
        $this->assertEquals('org', Validator::domainSuffix('org'));
        $this->assertEquals('net', Validator::domainSuffix('net'));
        $this->assertEquals('blog', Validator::domainSuffix('blog'));
        $this->assertEquals('codes', Validator::domainSuffix('codes'));
        $this->assertEquals('wtf', Validator::domainSuffix('wtf'));
        $this->assertEquals('sexy', Validator::domainSuffix('sexy'));
        $this->assertEquals('tennis', Validator::domainSuffix('tennis'));
        $this->assertEquals('versicherung', Validator::domainSuffix('versicherung'));
        $this->assertEquals('xn--3pxu8k', Validator::domainSuffix('点看'));
        $this->assertEquals('xn--80asehdb', Validator::domainSuffix('онлайн'));
        $this->assertEquals('xn--pssy2u', Validator::domainSuffix('大拿'));
        $this->assertEquals('co.uk', Validator::domainSuffix('co.uk'));
        $this->assertEquals('co.at', Validator::domainSuffix('co.at'));
        $this->assertEquals('or.at', Validator::domainSuffix('or.at'));
        $this->assertEquals('anything.bd', Validator::domainSuffix('anything.bd'));

        $this->assertNull(Validator::domainSuffix('süffix'));
        $this->assertNull(Validator::domainSuffix('idk'));
    }

    public function testValidateDomain()
    {
        $this->assertEquals('google.com', Validator::domain('google.com'));
        $this->assertEquals('example.xn--80asehdb', Validator::domain('example.xn--80asehdb'));
        $this->assertEquals('example.xn--80asehdb', Validator::domain('example.онлайн'));
        $this->assertEquals('yolo', Validator::domain('yolo', true));

        $this->assertNull(Validator::domain('www.google.com'));
        $this->assertNull(Validator::domain('yolo'));
        $this->assertNull(Validator::domain('subdomain.example.онлайн'));
    }

    public function testValidateSubdomain()
    {
        $this->assertEquals('www', Validator::subdomain('www'));
        $this->assertEquals('sub.domain', Validator::subdomain('sub.domain'));
        $this->assertEquals('sub.do.main', Validator::subdomain('SUB.DO.MAIN'));

        $this->assertNull(Validator::subdomain('sub_domain'));
    }

    public function testValidatePort()
    {
        $this->assertEquals(0, Validator::port(0));
        $this->assertEquals(8080, Validator::port(8080));
        $this->assertEquals(65535, Validator::port(65535));

        $this->assertNull(Validator::port(-1));
        $this->assertNull(Validator::port(65536));
    }

    public function testValidatePath()
    {
        $this->assertEquals('/FoO/bAr', Validator::path('/FoO/bAr'));
        $this->assertEquals('/foo-123/bar_456', Validator::path('/foo-123/bar_456'));
        $this->assertEquals('/~foo/!bar$/&baz\'', Validator::path('/~foo/!bar$/&baz\''));
        $this->assertEquals('/(foo)/*bar+', Validator::path('/(foo)/*bar+'));
        $this->assertEquals('/foo,bar;baz:', Validator::path('/foo,bar;baz:'));
        $this->assertEquals('/foo=bar@baz', Validator::path('/foo=bar@baz'));
        $this->assertEquals('/%22foo%22', Validator::path('/"foo"'));
        $this->assertEquals('/foo%5Cbar', Validator::path('/foo\\bar'));
        $this->assertEquals('/b%C3%B6%C3%9Fer/pfad', Validator::path('/bößer/pfad'));
        $this->assertEquals('/%3Chtml%3E', Validator::path('/<html>'));

        // Percent character not encoded (to %25) because %ba could be legitimate percent encoded character.
        $this->assertEquals('/foo%bar', Validator::path('/foo%bar'));

        // Percent character encoded because %ga isn't a valid percent encoded character.
        $this->assertEquals('/foo%25gar', Validator::path('/foo%gar'));
    }

    public function testValidateQuery()
    {
        $this->assertEquals('foo=bar', Validator::query('foo=bar'));
        $this->assertEquals('foo=bar', Validator::query('?foo=bar'));
        $this->assertEquals('foo1=bar&foo2=baz', Validator::query('foo1=bar&foo2=baz'));
        $this->assertEquals('.foo-=_bar~', Validator::query('.foo-=_bar~'));
        $this->assertEquals('%25foo!=$bar\'', Validator::query('%foo!=$bar\''));
        $this->assertEquals('(foo)=*bar+', Validator::query('(foo)=*bar+'));
        $this->assertEquals('f,o;o==bar:', Validator::query('f,o;o==bar:'));
        $this->assertEquals('@foo=/bar%3F', Validator::query('?@foo=/bar?'));
        $this->assertEquals('%22foo%22=bar', Validator::query('"foo"=bar'));
        $this->assertEquals('foo%23=bar', Validator::query('foo#=bar'));
        $this->assertEquals('f%C3%B6o=bar', Validator::query('föo=bar'));
        $this->assertEquals('boe%C3%9Fer=query', Validator::query('boeßer=query'));
        $this->assertEquals('foo%60=bar', Validator::query('foo`=bar'));
        $this->assertEquals('foo%25bar=baz', Validator::query('foo%25bar=baz'));
    }

    public function testValidateFragment()
    {
        $this->assertEquals('fragment', Validator::fragment('fragment'));
        $this->assertEquals('fragment', Validator::fragment('#fragment'));
        $this->assertEquals('fragment1234567890', Validator::fragment('fragment1234567890'));
        $this->assertEquals('-.fragment_~', Validator::fragment('-.fragment_~'));
        $this->assertEquals('%25!fragment$&', Validator::fragment('%!fragment$&'));
        $this->assertEquals('(\'fragment*)', Validator::fragment('(\'fragment*)'));
        $this->assertEquals('+,fragment;:', Validator::fragment('#+,fragment;:'));
        $this->assertEquals('@=fragment/?', Validator::fragment('@=fragment/?'));
        $this->assertEquals('%22fragment%22', Validator::fragment('#"fragment"'));
        $this->assertEquals('fragment%23', Validator::fragment('#fragment#'));
        $this->assertEquals('%23fragment', Validator::fragment('##fragment'));
        $this->assertEquals('fr%C3%A4gment', Validator::fragment('frägment'));
        $this->assertEquals('boe%C3%9Fesfragment', Validator::fragment('boeßesfragment'));
        $this->assertEquals('fragment%60', Validator::fragment('fragment`'));
        $this->assertEquals('fragm%E2%82%ACnt', Validator::fragment('fragm%E2%82%ACnt'));
    }

    /**
     * @param $validationResult
     * @param array $contains
     */
    private function urlValidationResultContains($validationResult, array $contains)
    {
        $this->assertIsArray($validationResult);

        foreach ($contains as $key => $value) {
            $this->assertArrayHasKey($key, $validationResult);
            $this->assertEquals($value, $validationResult[$key]);
        }
    }
}
