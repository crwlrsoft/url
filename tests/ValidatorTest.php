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

        $this->assertEquals('http', $validator->scheme('http'));
        $this->assertEquals('mailto', $validator->scheme('mailto'));
        $this->assertEquals('ssh', $validator->scheme('ssh'));
        $this->assertEquals('ftp', $validator->scheme('ftp'));
        $this->assertEquals('sftp', $validator->scheme('sftp'));
        $this->assertEquals('wss', $validator->scheme('wss'));
        $this->assertEquals('https', $validator->scheme('HTTPS'));

        $this->assertFalse($validator->scheme('1invalidscheme'));
        $this->assertFalse($validator->scheme('mäilto'));
    }

    public function testValidateUserOrPassword()
    {
        $validator = new Validator();

        $this->assertEquals('user', $validator->userOrPassword('user'));
        $this->assertEquals('pASS123', $validator->userOrPassword('pASS123'));
        $this->assertEquals('user-123', $validator->userOrPassword('user-123'));
        $this->assertEquals('P4ss.123', $validator->userOrPassword('P4ss.123'));
        $this->assertEquals('user_123', $validator->userOrPassword('user_123'));
        $this->assertEquals('p4ss~123', $validator->userOrPassword('p4ss~123'));
        $this->assertEquals('user%123', $validator->userOrPassword('user%123'));
        $this->assertEquals('p4ss-123!', $validator->userOrPassword('p4ss-123!'));
        $this->assertEquals('u$3r_n4m3!', $validator->userOrPassword('u$3r_n4m3!'));
        $this->assertEquals('p4$$&w0rD', $validator->userOrPassword('p4$$&w0rD'));
        $this->assertEquals('u$3r\'$_n4m3', $validator->userOrPassword('u$3r\'$_n4m3'));
        $this->assertEquals('(p4$$-w0rD)', $validator->userOrPassword('(p4$$-w0rD)'));
        $this->assertEquals('u$3r*n4m3', $validator->userOrPassword('u$3r*n4m3'));
        $this->assertEquals('p4$$+W0rD', $validator->userOrPassword('p4$$+W0rD'));
        $this->assertEquals('u$3r,n4m3', $validator->userOrPassword('u$3r,n4m3'));
        $this->assertEquals('P4ss;w0rd', $validator->userOrPassword('P4ss;w0rd'));
        $this->assertEquals('=u$3r=', $validator->userOrPassword('=u$3r='));

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

        $this->assertEquals('example.com', $validator->host('example.com'));
        $this->assertEquals('www.example.com', $validator->host('www.example.com'));
        $this->assertEquals('subdomain.example.com', $validator->host('subdomain.example.com'));
        $this->assertEquals('www.some-domain.io', $validator->host('www.some-domain.io'));
        $this->assertEquals('123456.co.uk', $validator->host('123456.co.uk'));
        $this->assertEquals('WWW.EXAMPLE.COM', $validator->host('WWW.EXAMPLE.COM'));
        $this->assertEquals('www-something.blog', $validator->host('www-something.blog'));
        $this->assertEquals('h4ck0r.software', $validator->host('h4ck0r.software'));
        $this->assertEquals('g33ks.org', $validator->host('g33ks.org'));
        $this->assertEquals('example.xn--80asehdb', $validator->host('example.xn--80asehdb'));
        $this->assertEquals('example.xn--80asehdb', $validator->host('example.онлайн'));
        $this->assertEquals('12.34.56.78', $validator->host('12.34.56.78'));
        $this->assertEquals('localhost', $validator->host('localhost'));
        $this->assertEquals('dev.local', $validator->host('dev.local'));

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

        $this->assertEquals('com', $validator->domainSuffix('com'));
        $this->assertEquals('org', $validator->domainSuffix('org'));
        $this->assertEquals('net', $validator->domainSuffix('net'));
        $this->assertEquals('blog', $validator->domainSuffix('blog'));
        $this->assertEquals('codes', $validator->domainSuffix('codes'));
        $this->assertEquals('wtf', $validator->domainSuffix('wtf'));
        $this->assertEquals('sexy', $validator->domainSuffix('sexy'));
        $this->assertEquals('tennis', $validator->domainSuffix('tennis'));
        $this->assertEquals('versicherung', $validator->domainSuffix('versicherung'));
        $this->assertEquals('xn--3pxu8k', $validator->domainSuffix('点看'));
        $this->assertEquals('xn--80asehdb', $validator->domainSuffix('онлайн'));
        $this->assertEquals('xn--pssy2u', $validator->domainSuffix('大拿'));
        $this->assertEquals('co.uk', $validator->domainSuffix('co.uk'));
        $this->assertEquals('co.at', $validator->domainSuffix('co.at'));
        $this->assertEquals('or.at', $validator->domainSuffix('or.at'));
        $this->assertEquals('anything.bd', $validator->domainSuffix('anything.bd'));

        $this->assertFalse($validator->domainSuffix('süffix'));
        $this->assertFalse($validator->domainSuffix('idk'));
    }

    public function testValidateDomain()
    {
        $validator = new Validator();

        $this->assertEquals('google.com', $validator->domain('google.com'));
        $this->assertEquals('example.xn--80asehdb', $validator->domain('example.xn--80asehdb'));
        $this->assertEquals('example.xn--80asehdb', $validator->domain('example.онлайн'));
        $this->assertEquals('yolo', $validator->domain('yolo', true));

        $this->assertFalse($validator->domain('www.google.com'));
        $this->assertFalse($validator->domain('yolo'));
        $this->assertFalse($validator->domain('subdomain.example.онлайн'));
    }

    public function testValidateSubdomain()
    {
        $validator = new Validator();

        $this->assertEquals('www', $validator->subdomain('www'));
        $this->assertEquals('sub.domain', $validator->subdomain('sub.domain'));
        $this->assertEquals('sub.do.main', $validator->subdomain('SUB.DO.MAIN'));

        $this->assertFalse($validator->subdomain('sub_domain'));
    }

    public function testValidatePort()
    {
        $validator = new Validator();

        $this->assertEquals(0, $validator->port(0));
        $this->assertEquals(0, $validator->port('0'));
        $this->assertEquals(8080, $validator->port(8080));
        $this->assertEquals(8080, $validator->port('8080'));
        $this->assertEquals(65535, $validator->port(65535));
        $this->assertEquals(65535, $validator->port('65535'));

        $this->assertFalse($validator->port(-1));
        $this->assertFalse($validator->port('invalid'));
        $this->assertFalse($validator->port(65536));
    }

    public function testValidatePath()
    {
        $validator = new Validator();

        $this->assertEquals('/FoO/bAr', $validator->path('/FoO/bAr'));
        $this->assertEquals('/foo-123/bar_456', $validator->path('/foo-123/bar_456'));
        $this->assertEquals('/~foo/!bar$/&baz\'', $validator->path('/~foo/!bar$/&baz\''));
        $this->assertEquals('/(foo)/*bar+', $validator->path('/(foo)/*bar+'));
        $this->assertEquals('/foo,bar;baz:', $validator->path('/foo,bar;baz:'));
        $this->assertEquals('/foo=bar@baz', $validator->path('/foo=bar@baz'));
        $this->assertEquals('/foo%25bar', $validator->path('/foo%bar'));
        $this->assertEquals('/%22foo%22', $validator->path('/"foo"'));
        $this->assertEquals('/foo%5Cbar', $validator->path('/foo\\bar'));
        $this->assertEquals('/b%C3%B6%C3%9Fer/pfad', $validator->path('/bößer/pfad'));
        $this->assertEquals('/%3Chtml%3E', $validator->path('/<html>'));

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

        $this->assertEquals('foo=bar', $validator->query('foo=bar'));
        $this->assertEquals('foo=bar', $validator->query('?foo=bar'));
        $this->assertEquals('foo1=bar&foo2=baz', $validator->query('foo1=bar&foo2=baz'));
        $this->assertEquals('.foo-=_bar~', $validator->query('.foo-=_bar~'));
        $this->assertEquals('%25foo!=$bar\'', $validator->query('%foo!=$bar\''));
        $this->assertEquals('(foo)=*bar+', $validator->query('(foo)=*bar+'));
        $this->assertEquals('f,o;o==bar:', $validator->query('f,o;o==bar:'));
        $this->assertEquals('@foo=/bar%3F', $validator->query('?@foo=/bar?'));
        $this->assertEquals('%22foo%22=bar', $validator->query('"foo"=bar'));
        $this->assertEquals('foo%23=bar', $validator->query('foo#=bar'));
        $this->assertEquals('f%C3%B6o=bar', $validator->query('föo=bar'));
        $this->assertEquals('boe%C3%9Fer=query', $validator->query('boeßer=query'));
        $this->assertEquals('foo%60=bar', $validator->query('foo`=bar'));
    }

    public function testValidateFragment()
    {
        $validator = new Validator();

        $this->assertEquals('fragment', $validator->fragment('fragment'));
        $this->assertEquals('fragment', $validator->fragment('#fragment'));
        $this->assertEquals('fragment1234567890', $validator->fragment('fragment1234567890'));
        $this->assertEquals('-.fragment_~', $validator->fragment('-.fragment_~'));
        $this->assertEquals('%25!fragment$&', $validator->fragment('%!fragment$&'));
        $this->assertEquals('(\'fragment*)', $validator->fragment('(\'fragment*)'));
        $this->assertEquals('+,fragment;:', $validator->fragment('#+,fragment;:'));
        $this->assertEquals('@=fragment/?', $validator->fragment('@=fragment/?'));
        $this->assertEquals('%22fragment%22', $validator->fragment('#"fragment"'));
        $this->assertEquals('fragment%23', $validator->fragment('#fragment#'));
        $this->assertEquals('%23fragment', $validator->fragment('##fragment'));
        $this->assertEquals('fr%C3%A4gment', $validator->fragment('frägment'));
        $this->assertEquals('boe%C3%9Fesfragment', $validator->fragment('boeßesfragment'));
        $this->assertEquals('fragment%60', $validator->fragment('fragment`'));
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
