<?php
declare(strict_types=1);

namespace Tests;

use Crwlr\QueryString\Query;
use Crwlr\Url\Url;
use Exception;
use PHPUnit\Framework\TestCase;

final class QueryStringTest extends TestCase
{
    public function testReturnsAnInstanceOfQueryWhenPhpVersion8OrAboveOtherwiseThrowsException(): void
    {
        $url = Url::parse('https://www.example.com/path?foo=bar');

        if (version_compare(PHP_VERSION, '8.0.0', '>=') && class_exists(Query::class)) {
            $this->assertInstanceOf(Query::class, $url->queryString());
        } else {
            $this->expectException(Exception::class);

            $url->queryString();
        }
    }

    /**
     * @throws Exception
     */
    public function testTheQueryMethodReturnValueIsInSyncWithQueryStringInstance(): void
    {
        $url = Url::parse('https://www.example.com/path?foo=bar');

        if (version_compare(PHP_VERSION, '8.0.0', '>=') && class_exists(Query::class)) {
            $url->queryString()->set('baz', 'quz');

            $this->assertEquals('foo=bar&baz=quz', $url->query());

            $this->assertEquals('https://www.example.com/path?foo=bar&baz=quz', $url->__toString());
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * @throws Exception
     */
    public function testTheQueryArrayMethodReturnValueIsInSyncWithQueryStringInstance(): void
    {
        $url = Url::parse('https://www.example.com/path?foo=bar');

        if (version_compare(PHP_VERSION, '8.0.0', '>=') && class_exists(Query::class)) {
            $url->queryString()->set('baz', 'quz');

            $this->assertEquals(['foo' => 'bar', 'baz' => 'quz'], $url->queryArray());

            $this->assertEquals('https://www.example.com/path?foo=bar&baz=quz', $url->__toString());
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * @throws Exception
     */
    public function testTheQueryStringCanBeAccessedViaMagicGetter(): void
    {
        $url = Url::parse('https://www.example.com/path?foo=bar');

        if (version_compare(PHP_VERSION, '8.0.0', '>=') && class_exists(Query::class)) {
            $url->queryString()->set('baz', 'quz');

            $this->assertEquals(['foo' => 'bar', 'baz' => 'quz'], $url->queryArray());

            $this->assertEquals('https://www.example.com/path?foo=bar&baz=quz', $url->__toString());
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * @throws Exception
     */
    public function testItStillWorksToSetTheQueryViaQueryMethodAfterQueryStringWasUsed(): void
    {
        $url = Url::parse('https://www.example.com/path?foo=bar');

        if (version_compare(PHP_VERSION, '8.0.0', '>=') && class_exists(Query::class)) {
            $url->queryString()->set('baz', 'quz');

            $url->query('yo=lo');

            $this->assertEquals('yo=lo', $url->query());

            $this->assertEquals(['yo' => 'lo'], $url->queryArray());

            $this->assertEquals('https://www.example.com/path?yo=lo', $url->toString());
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * @throws Exception
     */
    public function testItStillWorksToSetTheQueryViaQueryArrayMethodAfterQueryStringWasUsed(): void
    {
        $url = Url::parse('https://www.example.com/path?foo=bar');

        if (version_compare(PHP_VERSION, '8.0.0', '>=') && class_exists(Query::class)) {
            $url->queryString()->set('baz', 'quz');

            $url->queryArray(['boo' => 'yah']);

            $this->assertEquals('boo=yah', $url->query());

            $this->assertEquals(['boo' => 'yah'], $url->queryArray());

            $this->assertEquals('https://www.example.com/path?boo=yah', $url->toString());
        } else {
            $this->assertTrue(true);
        }
    }
}
