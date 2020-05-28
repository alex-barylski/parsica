<?php declare(strict_types=1);

namespace Tests\Mathias\ParserCombinator;

use Mathias\ParserCombinator\PHPUnit\ParserTestCase;
use function Mathias\ParserCombinator\{char, either, ignore, into1, intoNew1, optional, seq, string};

final class TypecastingTest extends ParserTestCase
{
    /** @test */
    public function into1()
    {
        $parser =
            char('a')->followedBy(char('b'))
                ->into1('strtoupper');

        $expected = "AB";

        $this->assertParse($expected, $parser, "abca");
        $this->markTestIncomplete("@TODO Replace with 0.2 version");
    }
    
    
    /** @test */
    public function intoNew1()
    {
        $parser = intoNew1(
            seq(char('a'), char('b')),
            __NAMESPACE__.'\\MyType1'
        );

        $expected = new MyType1("ab");

        $this->assertParse($expected, $parser, "abc");
        $this->markTestIncomplete("@TODO Replace with 0.2 version");
    }

}

class MyType1
{
    private $val;

    function __construct($val)
    {
        $this->val = $val;
    }
}