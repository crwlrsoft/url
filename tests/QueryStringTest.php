<?php

use Crwlr\QueryString\Query;
use Crwlr\Url\Url;

/** @var \PHPUnit\Framework\TestCase $this */

test('ReturnsAnInstance', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');

    $this->assertInstanceOf(Query::class, $url->queryString());
});

test('TheQueryMethodReturnValueIsInSyncWithQueryStringInstance', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');
    $url->queryString()->set('baz', 'quz');

    $this->assertEquals('foo=bar&baz=quz', $url->query());
    $this->assertEquals('https://www.example.com/path?foo=bar&baz=quz', $url->__toString());
});

test('TheQueryArrayMethodReturnValueIsInSyncWithQueryStringInstance', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');
    $url->queryString()->set('baz', 'quz');

    $this->assertEquals(['foo' => 'bar', 'baz' => 'quz'], $url->queryArray());
    $this->assertEquals('https://www.example.com/path?foo=bar&baz=quz', $url->__toString());
});

test('TheQueryStringCanBeAccessedViaMagicGetter', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');
    $url->queryString()->set('baz', 'quz');

    $this->assertEquals(['foo' => 'bar', 'baz' => 'quz'], $url->queryArray());
    $this->assertEquals('https://www.example.com/path?foo=bar&baz=quz', $url->__toString());
});

test('ItStillWorksToSetTheQueryViaQueryMethodAfterQueryStringWasUsed', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');
    $url->queryString()->set('baz', 'quz');
    $url->query('yo=lo');

    $this->assertEquals('yo=lo', $url->query());
    $this->assertEquals(['yo' => 'lo'], $url->queryArray());
    $this->assertEquals('https://www.example.com/path?yo=lo', $url->toString());
});

test('ItStillWorksToSetTheQueryViaQueryArrayMethodAfterQueryStringWasUsed', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');
    $url->queryString()->set('baz', 'quz');
    $url->queryArray(['boo' => 'yah']);

    $this->assertEquals('boo=yah', $url->query());
    $this->assertEquals(['boo' => 'yah'], $url->queryArray());
    $this->assertEquals('https://www.example.com/path?boo=yah', $url->toString());
});
