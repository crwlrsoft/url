<?php

use Crwlr\QueryString\Query;
use Crwlr\Url\Url;

it('returns an instance of query', function () {
    expect(Url::parse('https://www.example.com/path?foo=bar'))
        ->queryString()->toBeInstanceOf(Query::class);
});

test('the query method return value is in sync with query string instance', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');
    $url->queryString()->set('baz', 'quz');

    expect($url)
        ->query()->toBe('foo=bar&baz=quz')
        ->toString()->toBe('https://www.example.com/path?foo=bar&baz=quz');
});

test('the query array method return value is in sync with query string instance', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');
    $url->queryString()->set('baz', 'quz');

    expect($url)
        ->queryArray()->toBe(['foo' => 'bar', 'baz' => 'quz'])
        ->toString()->toBe('https://www.example.com/path?foo=bar&baz=quz');
});

test('the query string can be accessed via magic getter', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');
    $url->queryString()->set('baz', 'quz');

    expect($url)
        ->queryArray()->toBe(['foo' => 'bar', 'baz' => 'quz'])
        ->toString()->toBe('https://www.example.com/path?foo=bar&baz=quz');
});

it('still works to set the query via query method after query string was used', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');
    $url->queryString()->set('baz', 'quz');
    $url->query('yo=lo');

    expect($url)
        ->query()->toBe('yo=lo')
        ->queryArray()->toBe(['yo' => 'lo'])
        ->toString()->toBe('https://www.example.com/path?yo=lo');
});

it('still works to set the query via query array method after query string was used', function () {
    $url = Url::parse('https://www.example.com/path?foo=bar');
    $url->queryString()->set('baz', 'quz');
    $url->queryArray(['boo' => 'yah']);

    expect($url)
        ->query()->toBe('boo=yah')
        ->queryArray()->toBe(['boo' => 'yah'])
        ->toString()->toBe('https://www.example.com/path?boo=yah');
});
