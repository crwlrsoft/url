<?php
declare(strict_types=1);

use Crwlr\Url\Domain;
use PHPUnit\Framework\TestCase;

final class DomainTest extends TestCase
{
    /**
     * The domain class is really simple and assumes input validation happens somewhere else, so this test
     * is rather short ;p
     */
    public function testDomain()
    {
        $domain = new Domain('example.com');
        $this->assertInstanceOf(Domain::class, $domain);
        $this->assertEquals('example', $domain->label());
        $this->assertEquals('com', $domain->suffix());
        $this->assertEquals('example.com', $domain->__toString());

        $domain = new Domain('notadomain');
        $this->assertInstanceOf(Domain::class, $domain);
        $this->assertNull($domain->label());
        $this->assertNull($domain->suffix());
        $this->assertEquals('', $domain->__toString());
    }
}
