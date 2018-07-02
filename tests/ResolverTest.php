<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ResolverTest extends TestCase
{
    /**
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    public function testResolveRelativeUrls()
    {
        $baseUrlObject = $this->getBaseUrlObject();
        $resolver = new \Crwlr\Url\Resolver();

        $resolved = $resolver->resolve('test', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/test');

        $resolved = $resolver->resolve('.', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/');

        $resolved = $resolver->resolve('./test', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/test');

        $resolved = $resolver->resolve('../test', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/test');

        $resolved = $resolver->resolve('../../test', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/test');

        $resolved = $resolver->resolve('../../../test', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/test');

        $resolved = $resolver->resolve('?test=true', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/baz?test=true');

        $resolved = $resolver->resolve('foo/../test', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/test');

        $resolved = $resolver->resolve('bar/./baz', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/bar/baz');

        $resolved = $resolver->resolve('..', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/');

        $resolved = $resolver->resolve('#fragment', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/baz#fragment');

        $resolved = $resolver->resolve('?query=string', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/baz?query=string');

        $resolved = $resolver->resolve('//www.google.com', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.google.com');

        $resolved = $resolver->resolve('?query=string#fragment', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/baz?query=string#fragment');

        $resolved = $resolver->resolve('/some/path', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/some/path');

        // Base url path is directory (trailing slash)
        $baseUrlObject = $this->getBaseUrlObject('https://www.example.com/foo/bar/baz/');

        $resolved = $resolver->resolve('test', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/baz/test');

        $resolved = $resolver->resolve('../test', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/test');

        $resolved = $resolver->resolve('./test', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/baz/test');

        $resolved = $resolver->resolve('.././one/./two/./../three/./four/../five', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/foo/bar/one/three/five');

        $resolved = $resolver->resolve('/one/./two/./../three/four/.', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/one/three/four/');

        $resolved = $resolver->resolve('/one/./two/./../three/four/..', $baseUrlObject);
        $this->assertEquals($resolved->toString(), 'https://www.example.com/one/three/');
    }

    public function testResolvePaths()
    {
        $resolver = new \Crwlr\Url\Resolver();

        $this->assertEquals(
            $resolver->resolvePath('baz', '/foo/bar'),
            '/foo/baz'
        );

        $this->assertEquals(
            $resolver->resolvePath('./../.././really/./short/path', '/some/pretty/long/path'),
            '/some/really/short/path'
        );

        $this->assertEquals(
            $resolver->resolvePath('/different/stuff/../path', '/some/path'),
            '/different/path'
        );
    }

    /**
     * @param string $url
     * @return \Crwlr\Url\Url
     * @throws \Crwlr\Url\Exceptions\InvalidUrlException
     */
    private function getBaseUrlObject($url = '')
    {
        if ($url === '') {
            $url = 'https://www.example.com/foo/bar/baz';
        }

        return new Crwlr\Url\Url($url);
    }
}
