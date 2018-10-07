<?php
declare(strict_types=1);

use Crwlr\Url\Helpers;
use Crwlr\Url\Schemes;
use Crwlr\Url\Suffixes;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testGetSuffixesAndSchemesInstances()
    {
        $this->assertInstanceOf(Suffixes::class, Helpers::suffixes());
        $this->assertInstanceOf(Schemes::class, Helpers::schemes());
    }

    public function testGetDomainFromHost()
    {
        $testCases = [
            'www.example.com' => 'example.com',
            'www.example.org' => 'example.org',
            'sub.domain.example.edu.tt' => 'example.edu.tt',
            'jobs.example.sálat.no' => 'example.xn--slat-5na.no',
            'example' => null,
            '127.0.0.1' => null,
        ];

        foreach ($testCases as $host => $domain) {
            $this->assertEquals($domain, Helpers::getDomainFromHost($host));
        }
    }

    public function testGetDomainLabelFromHost()
    {
        $testCases = [
            'www.label.com' => 'label',
            'sub.domain.something.org' => 'something',
            'jobs.exámple.sálat.no' => 'xn--exmple-qta',
            'example' => null,
            '127.0.0.1' => null,
        ];

        foreach ($testCases as $host => $domain) {
            $this->assertEquals($domain, Helpers::getDomainLabelFromHost($host));
        }
    }

    public function testGetDomainSuffixFromHost()
    {
        $testCases = [
            'www.example.com' => 'com',
            'example.co.uk' => 'co.uk',
            'foo.bar' => 'bar',
            'foo.bar.foo.bar' => 'bar',
            'something.anything.kawasaki.jp' => 'anything.kawasaki.jp',
            'example.målselv.no' => 'xn--mlselv-iua.no',
            'localhost' => null,
        ];

        foreach ($testCases as $host => $domainSuffix) {
            $this->assertEquals($domainSuffix, Helpers::getDomainSuffixFromHost($host));
        }
    }

    public function testGetDomainLabelFromDomain()
    {
        $testCases = [
            'label.com' => 'label',
            'something.org' => 'something',
            'exámple.sálat.no' => 'xn--exmple-qta',
            'example' => null,
            '127.0.0.1' => null,
        ];

        foreach ($testCases as $host => $domain) {
            $this->assertEquals($domain, Helpers::getDomainLabelFromHost($host));
        }
    }

    public function testGetSubdomainFromHost()
    {
        $testCases = [
            'www.example.com' => 'www',
            'jobs.example.com' => 'jobs',
            'sub.domain.example.com' => 'sub.domain',
            'some.ridiculously.123.long.sub.domain.example.com' => 'some.ridiculously.123.long.sub.domain',
            'foo.bár.báz.sálat.no' => 'foo.xn--br-mia',
            'example.com' => null,
        ];

        foreach ($testCases as $host => $subdomain) {
            $this->assertEquals($subdomain, Helpers::getSubdomainFromHost($host));
        }
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
            Helpers::queryStringToArray('k.1=v.1&k.2[s.k1]=v.2&k.2[s.k2]=v.3'),
            [
                'k.1' => 'v.1',
                'k.2' => [
                    's.k1' => 'v.2',
                    's.k2' => 'v.3',
                ]
            ]
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

    public function testContainsCharactersNotAllowedInHost()
    {
        $this->assertTrue(Helpers::containsCharactersNotAllowedInHost('www.éxample.com'));
        // Fake "a", special character (idn domain).
        $this->assertTrue(Helpers::containsCharactersNotAllowedInHost('www.са.com'));
        $this->assertTrue(Helpers::containsCharactersNotAllowedInHost('under_score.example.com'));
        $this->assertTrue(Helpers::containsCharactersNotAllowedInHost('www.example.com', true));

        $this->assertFalse(Helpers::containsCharactersNotAllowedInHost('www.example123.com'));
        $this->assertFalse(Helpers::containsCharactersNotAllowedInHost('example123', true));
    }

    public function testStripFromEnd()
    {
        $this->assertEquals(Helpers::stripFromEnd('examplestring', 'string'), 'example');
        $this->assertEquals(Helpers::stripFromEnd('examplestring', 'strong'), 'examplestring');
        $this->assertEquals(Helpers::stripFromEnd('examplestring', 'strin'), 'examplestring');
    }
}
