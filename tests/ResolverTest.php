<?php

use Crwlr\Url\Resolver;
use Crwlr\Url\Url;

test('ResolveRelativeUrls', function () {
    $baseUrlObject = new Url('https://www.example.com/foo/bar/baz');
    $resolver = new Resolver();

    $resolved = $resolver->resolve('test', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/test', $resolved->toString());

    $resolved = $resolver->resolve('.', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/', $resolved->toString());

    $resolved = $resolver->resolve('./test', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/test', $resolved->toString());

    $resolved = $resolver->resolve('../test', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/test', $resolved->toString());

    $resolved = $resolver->resolve('../../test', $baseUrlObject);
    $this->assertEquals('https://www.example.com/test', $resolved->toString());

    $resolved = $resolver->resolve('../../../test', $baseUrlObject);
    $this->assertEquals('https://www.example.com/test', $resolved->toString());

    $resolved = $resolver->resolve('?test=true', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/baz?test=true', $resolved->toString());

    $resolved = $resolver->resolve('foo/../test', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/test', $resolved->toString());

    $resolved = $resolver->resolve('bar/./baz', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/bar/baz', $resolved->toString());

    $resolved = $resolver->resolve('..', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/', $resolved->toString());

    $resolved = $resolver->resolve('#fragment', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/baz#fragment', $resolved->toString());

    $resolved = $resolver->resolve('?query=string', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/baz?query=string', $resolved->toString());

    $resolved = $resolver->resolve('//www.google.com', $baseUrlObject);
    $this->assertEquals('https://www.google.com', $resolved->toString());

    $resolved = $resolver->resolve('?query=string#fragment', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/baz?query=string#fragment', $resolved->toString());

    $resolved = $resolver->resolve('/some/path', $baseUrlObject);
    $this->assertEquals('https://www.example.com/some/path', $resolved->toString());

    // Base URL path is directory (trailing slash)
    $baseUrlObject = new Url('https://www.example.com/foo/bar/baz/');

    $resolved = $resolver->resolve('test', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/baz/test', $resolved->toString());

    $resolved = $resolver->resolve('../test', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/test', $resolved->toString());

    $resolved = $resolver->resolve('./test', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/baz/test', $resolved->toString());

    $resolved = $resolver->resolve('.././one/./two/./../three/./four/../five', $baseUrlObject);
    $this->assertEquals('https://www.example.com/foo/bar/one/three/five', $resolved->toString());

    $resolved = $resolver->resolve('/one/./two/./../three/four/.', $baseUrlObject);
    $this->assertEquals('https://www.example.com/one/three/four/', $resolved->toString());

    $resolved = $resolver->resolve('/one/./two/./../three/four/..', $baseUrlObject);
    $this->assertEquals('https://www.example.com/one/three/', $resolved->toString());

    $relativeBaseUrl = new Url('/foo/bar/baz?query=string#fragment');
    $resolved = $resolver->resolve('.././one/./two/./../three', $relativeBaseUrl);
    $this->assertEquals('/foo/one/three', $resolved->toString());
});

test('ResolveRelativeUrlAgainstBaseUrlWithEmptyPath', function () {
    $baseUrlObject = Url::parse('https://www.crwlr.software');
    $resolver = new Resolver();
    $resolved = $resolver->resolve('/privacy', $baseUrlObject);
    $this->assertEquals('https://www.crwlr.software/privacy', $resolved->toString());
});

test('ResolveRelativePathWithoutLeadingSlashAgainstBaseUrlWithEmptyPath', function () {
    $resolver = new Resolver();
    $this->assertEquals(
        'https://www.otsch.codes/foo',
        $resolver->resolve('foo', Url::parse('https://www.otsch.codes'))
    );
});

test('ResolveAbsoluteUrl', function () {
    // When resolve() is called with an absolute URL as subject, it should just return this absolute URL.
    $baseUrlObject = new Url('https://www.example.com/foo/bar/baz');
    $resolver = new Resolver();

    $resolved = $resolver->resolve('http://www.crwlr.software/blog', $baseUrlObject);
    $this->assertEquals('http://www.crwlr.software/blog', $resolved->toString());

    $resolved = $resolver->resolve('mailto:john@example.com', $baseUrlObject);
    $this->assertEquals('mailto:john@example.com', $resolved->toString());

    $resolved = $resolver->resolve('//www.crwlr.software/blog', $baseUrlObject);
    $this->assertEquals('https://www.crwlr.software/blog', $resolved->toString());

    $relativeBaseUrl = new Url('/foo/bar?query=string#fragment');
    $resolved = $resolver->resolve('https://www.example.com/examples', $relativeBaseUrl);
    $this->assertEquals('https://www.example.com/examples', $resolved);
});

test('ResolvePaths', function () {
    // Resolve a relative path against an absolute path.
    $resolver = new Resolver();

    $this->assertEquals('/foo/baz', $resolver->resolvePath('baz', '/foo/bar'));

    $this->assertEquals(
        '/some/really/short/path',
        $resolver->resolvePath('./../.././really/./short/path', '/some/pretty/long/path')
    );

    $this->assertEquals('/different/path', $resolver->resolvePath('/different/stuff/../path', '/some/path'));
});
