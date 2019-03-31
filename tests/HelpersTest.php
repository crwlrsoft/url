<?php
declare(strict_types=1);

use Crwlr\Url\Helpers;
use Crwlr\Url\Schemes;
use Crwlr\Url\Suffixes;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testGetHelperClassInstancesStatically()
    {
        $this->assertInstanceOf(Suffixes::class, Helpers::suffixes());
        $this->assertInstanceOf(Schemes::class, Helpers::schemes());
    }

    public function testBuildUrlFromComponents()
    {
        $this->assertEquals(
            'https://user:pass@www.example.com:1234/foo/bar?query=string#fragment',
            Helpers::buildUrlFromComponents([
                'scheme' => 'https',
                'user' => 'user',
                'pass' => 'pass',
                'host' => 'www.example.com',
                'port' => 1234,
                'path' => '/foo/bar',
                'query' => 'query=string',
                'fragment' => 'fragment',
            ])
        );
    }

    /**
     * This test especially targets a problem in parse_str() which is used in the Parser class to convert a query
     * string to array. The problem is, that dots within keys in the query string are replaced with underscores.
     * For more information see https://github.com/crwlrsoft/url/issues/2
     */
    public function testQueryStringToArray()
    {
        $this->assertEquals(
            [
                'k.1' => 'v.1',
                'k.2' => [
                    's.k1' => 'v.2',
                    's.k2' => 'v.3',
                ]
            ],
            Helpers::queryStringToArray('k.1=v.1&k.2[s.k1]=v.2&k.2[s.k2]=v.3')
        );
    }

    public function testGetStandardPortsByScheme()
    {
        $this->assertEquals(21, Helpers::getStandardPortByScheme('ftp'));
        $this->assertEquals(9418, Helpers::getStandardPortByScheme('git'));
        $this->assertEquals(80, Helpers::getStandardPortByScheme('http'));
        $this->assertEquals(443, Helpers::getStandardPortByScheme('https'));
        $this->assertEquals(143, Helpers::getStandardPortByScheme('imap'));
        $this->assertEquals(194, Helpers::getStandardPortByScheme('irc'));
        $this->assertEquals(2049, Helpers::getStandardPortByScheme('nfs'));
        $this->assertEquals(873, Helpers::getStandardPortByScheme('rsync'));
        $this->assertEquals(115, Helpers::getStandardPortByScheme('sftp'));
        $this->assertEquals(25, Helpers::getStandardPortByScheme('smtp'));

        $this->assertNull(Helpers::getStandardPortByScheme('unknownscheme'));
    }

    public function testStripFromEnd()
    {
        $this->assertEquals('example', Helpers::stripFromEnd('examplestring', 'string'));
        $this->assertEquals('examplestring', Helpers::stripFromEnd('examplestring', 'strong'));
        $this->assertEquals('examplestring', Helpers::stripFromEnd('examplestring', 'strin'));
    }

    public function testStripFromStart()
    {
        $this->assertEquals('string', Helpers::stripFromStart('examplestring', 'example'));
        $this->assertEquals('examplestring', Helpers::stripFromStart('examplestring', 'eggsample'));
        $this->assertEquals('examplestring', Helpers::stripFromStart('examplestring', 'xample'));
    }

    public function testReplaceFirstOccurrence()
    {
        $this->assertEquals('foo bas baz bar', Helpers::replaceFirstOccurrence('bar', 'bas', 'foo bar baz bar'));
        $this->assertEquals('foo bar bar', Helpers::replaceFirstOccurrence('baz', 'bar', 'foo bar baz'));
    }

    public function testStartsWith()
    {
        $this->assertTrue(Helpers::startsWith('Raindrops Keep Fallin\' on My Head', 'Raindrops Keep'));
        $this->assertFalse(Helpers::startsWith('Raindrops Keep Fallin\' on My Head', 'Braindrops Keep'));
    }

    public function testContainsXBeforeFirstY()
    {
        $this->assertTrue(Helpers::containsXBeforeFirstY('one-two-three-two', '-', 'two'));
        $this->assertFalse(Helpers::containsXBeforeFirstY('one-two-three-two', 'three', 'two'));
    }
}
