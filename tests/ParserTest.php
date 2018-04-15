<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testParseUrls()
    {
        $parser = new \Crwlr\Url\Parser();
        $parsed = $parser->parse('https://username:password@www.example.com:8081/foo/bar?query=string#fragment');

        $this->assertEquals(
            $parsed,
            [
                'scheme' => 'https',
                'host' => 'www.example.com',
                'port' => 8081,
                'user' => 'username',
                'pass' => 'password',
                'path' => '/foo/bar',
                'query' => 'query=string',
                'fragment' => 'fragment',
                'url' => 'https://username:password@www.example.com:8081/foo/bar?query=string#fragment',
            ]
        );

        $parsed = $parser->parse('https://username:password@www.example.com:8081/foo/bar?query=string#fragment', true);

        $this->assertEquals(
            $parsed,
            [
                'scheme' => 'https',
                'host' => 'www.example.com',
                'port' => 8081,
                'user' => 'username',
                'pass' => 'password',
                'path' => '/foo/bar',
                'query' => 'query=string',
                'fragment' => 'fragment',
                'url' => 'https://username:password@www.example.com:8081/foo/bar?query=string#fragment',
                'domainSuffix' => 'com',
                'domain' => 'example.com',
                'subdomain' => 'www',
            ]
        );
    }

    public function testGetDomainFromHost()
    {
        $parser = new \Crwlr\Url\Parser();
        $this->assertEquals($parser->getDomainFromHost('www.example.com'), 'example.com');
        $this->assertEquals($parser->getDomainFromHost('www.example.org', 'org'), 'example.org');
    }

    public function testGetSubdomainFromHost()
    {
        $parser = new \Crwlr\Url\Parser();
        $this->assertEquals($parser->getSubdomainFromHost('www.example.com'), 'www');
        $this->assertEquals($parser->getSubdomainFromHost('jobs.example.com'), 'jobs');
    }

    public function testStripFromEnd()
    {
        $parser = new \Crwlr\Url\Parser();
        $this->assertEquals(\Crwlr\Url\Parser::stripFromEnd('examplestring', 'string'), 'example');
        $this->assertEquals(\Crwlr\Url\Parser::stripFromEnd('examplestring', 'strong'), 'examplestring');
    }
}
