<?php declare(strict_types=1);

namespace Tests\Mathias\ParserCombinator;

use Mathias\ParserCombinator\PHPUnit\ParserTestCase;
use function Mathias\ParserCombinator\{any,
    atLeastOne,
    char,
    collect,
    digit,
    either,
    float,
    ignore,
    into1,
    intoNew1,
    optional,
    seq,
    space,
    string
};

final class CombinatorsTest extends ParserTestCase
{

    /** @test */
    public function ignore()
    {
        $parser = ignore(char('a'));
        $this->assertParse("", $parser, "abc");
        $this->assertRemain("bc", $parser, "abc");

        $parser = string('abcd')
            ->followedBy(ignore(char('-')))
            ->followedBy(string('efgh'));
        $this->assertParse("abcdefgh", $parser, "abcd-efgh");
    }

    /** @test */
    public function optional()
    {
        $parser = char('a')->optional();
        $this->assertParse("a", $parser, "abc");
        $this->assertRemain("bc", $parser, "abc");

        $this->assertParse("", $parser, "bc");
        $this->assertRemain("bc", $parser, "bc");

        $parser = string('abcd')
            ->followedBy(optional(ignore(char('-'))))
            ->followedBy(string('efgh'));
        $this->assertParse("abcdefgh", $parser, "abcd-efgh");
        $this->assertParse("abcdefgh", $parser, "abcdefgh");
    }

    /** @test */
    public function either()
    {
        $parser = either(char('a'), char('b'));

        $this->assertParse("a", $parser, "abc");
        $this->assertRemain("bc", $parser, "abc");
        $this->assertParse("b", $parser, "bc");
        $this->assertRemain("c", $parser, "bc");
        $this->assertNotParse($parser, "cd");
    }

    /** @test */
    public function seq()
    {
        $parser = seq(char('a'), char('b'));

        $this->assertParse("ab", $parser, "abc");
        $this->assertRemain("c", $parser, "abc");
        $this->assertNotParse($parser, "acc");
        $this->assertNotParse($parser, "cab");
    }

    /** @test */
    public function collect()
    {
        $parser =
            collect(
                string("Hello")
                    ->followedBy(
                        optional(space())->ignore()
                    )
                    ->followedBy(
                        char(',')->ignore()
                    )
                    ->followedBy(
                        optional(space())->ignore()
                    ),
                string("world")
                    ->followedBy(
                        char('!')->ignore()
                    )
            );

        $expected = ["Hello", "world"];

        $this->assertParse($expected, $parser, "Hello , world!");
        $this->assertParse($expected, $parser, "Hello,world!");
    }

    /** @test */
    public function collectFails()
    {
        $parser =
            collect(
                string("Hello"),
                string("world")
            );
        $this->assertNotParse($parser, "Helloplanet");
    }

    /**
     * @test
     */
    public function atLeastOne()
    {
        $parser = atLeastOne(char('a'));
        $this->assertParse("a", $parser, "a");
        $this->assertParse("aa", $parser, "aa");
        $this->assertParse("aa", $parser, "aabb");
        $this->assertNotParse($parser, "bb");
    }

    /** @test */
    public function any_()
    {
        $symbol = any(string("€"), string("$"));
        $amount = float()->into1('floatval');
        $money = collect($symbol, $amount);

        $this->assertParse("€", $symbol, "€");
        $this->assertParse(15.23, $amount, "15.23");
        $this->assertParse(["€", 15.23], $money, "€15.23");
        $this->assertParse(["$", 15], $money, "$15");
        $this->assertNotParse($money, "£12.13");
    }

}
