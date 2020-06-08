<?php declare(strict_types=1);

namespace Mathias\ParserCombinator\Parser;

use Exception;
use Mathias\ParserCombinator\ParseResult\ParseResult;
use function Mathias\ParserCombinator\ParseResult\{fail, succeed};

/**
 * A parser is any function that takes a string input and returns a {@see ParseResult}. The Parser class is fundamentally
 * just a wrapper around such functions. The {@see Parser::make()} static constructor takes a callable that does the
 * actual parsing. Usually you don't need to instantiate this class directly. Instead, build your parser from existing
 * parsers and combinators.
 *
 * At the moment, there is no Parser interface, and no Parser abstract class to extend from. This is intentional, but
 * will be changed if we find use cases where those would be the best solutions.
 *
 * @internal
 * @template T
 */
final class Parser
{
    /**
     * @var callable(string) : ParseResult<T> $parserF
     */
    private $parserFunction;

    /** @var 'not-recursive'|'awaiting-recurse'|'recursion-was-setup' */
    private string $recursionStatus;

    /**
     * @param callable(string) : ParseResult<T> $parserFunction
     * @param 'not-recursive'|'awaiting-recurse'|'recursion-was-setup' $recursionStatus
     */
    function __construct(callable $parserFunction, string $recursionStatus)
    {
        $this->parserFunction = $parserFunction;
        $this->recursionStatus = $recursionStatus;
    }

    /**
     * Make a recursive parser. Use {@see recursive()}.
     *
     * @return Parser<T>
     */
    public static function recursive(): Parser
    {
        return new Parser(
            // Make a placeholder parser that will throw when you try to run it.
            function (string $input) {
                throw new Exception(
                    "Can't run a recursive parser that hasn't been setup properly yet. A parser created by recursive(), "
                    . "must then be called with ->recurse(Parser) before it can be used."
                );
            },
            'awaiting-recurse');
    }

    /**
     * Recurse on a parser. Used in combination with {@see recursive()}.
     *
     * This method does not return anything, and instead mutates the parser. After calling this method however, the parser
     * behaves like a regular parser.
     *
     * @param Parser<T> $parser
     */
    public function recurse(Parser $parser): void
    {
        switch ($this->recursionStatus) {
            case 'not-recursive':
                throw new Exception(
                    "You can't recurse on a non-recursive parser. Create a recursive parser first using recursive(), "
                    . "then call ->recurse() on it."
                );
            case 'recursion-was-setup':
                throw new Exception("You can only call recurse() once on a recursive parser.");
            case 'awaiting-recurse':
                // Replace the placeholder parser from recursive() with a call to the inner parser. This must be dynamic,
                // because it's possible that the inner parser is also a recursive parser that has not been setup yet.
                $this->parserFunction = fn(string $input) => $parser->run($input);
                $this->recursionStatus = 'recursion-was-setup';
                break;
            default:
                throw new Exception("Unexpected recursionStatus value");
        }

    }

    /**
     * Run the parser on an input
     *
     * @return ParseResult<T>
     */
    public function run(string $input): ParseResult
    {
        $f = $this->parserFunction;
        return $f($input);
    }

    /**
     * Label a parser. When a parser fails, instead of a generated error message, you'll see your label.
     * eg (char(':')->followedBy(char(')')).followedBy(char(')')).
     *
     * @return Parser<T>
     */
    public function label(string $label): Parser
    {
        return Parser::make(function (string $input) use ($label) : ParseResult {
            $result = $this->run($input);
            return ($result->isSuccess())
                ? $result
                : fail($label, $input);
        });
    }

    /**
     * Make a new parser. This is the constructor for all regular use.
     *
     * @param callable(string) : ParseResult<T> $parserFunction
     *
     * @return Parser<T>
     */
    public static function make(callable $parserFunction): Parser
    {
        return new Parser($parserFunction, 'not-recursive');
    }

    /**
     * Parse something, strip it from the remaining input, but discard the parsed value.
     *
     * @return Parser<T>
     */
    public function ignore(): Parser
    {
        return Parser::make(function (string $input): ParseResult {
            return $this->run($input)->discard();
        });
    }

    /**
     * @return Parser<T>
     * @see optional()
     */
    public function optional(): Parser
    {
        return Parser::make(function (string $input): ParseResult {
            $r1 = $this->run($input);
            return $r1->isSuccess() ? $r1 : succeed("", $input);
        });
    }

    /**
     * @param Parser<T2> $second
     *
     * @return Parser<T2>
     * @deprecated 0.2
     * @see seq()
     * @template T2
     */
    public function followedBy(Parser $second): Parser
    {
        return Parser::make(function (string $input) use ($second) : ParseResult {
            $r1 = $this->run($input);
            if ($r1->isSuccess()) {
                $r2 = $second->continueFrom($r1);
                if ($r2->isSuccess()) {
                    return succeed($r2->parsed(), $r2->remaining());
                }
                return fail("seq (... {$r2->expected()})", $r2->got());
            }
            return fail("seq ({$r1->expected()} ...)", $r1->got());
        });

    }

    /**
     * Take the remaining input from the result and parses it
     *
     * @deprecated Doesn't have a test
     */
    public function continueFrom(ParseResult $result): ParseResult
    {
        return $this->run($result->remaining());
    }

    /**
     * @param Parser<T> $second
     *
     * @return Parser<T>
     * @deprecated 0.2
     * @see either()
     */
    public function or(Parser $second): Parser
    {
        return Parser::make(function (string $input) use ($second) : ParseResult {
            $r1 = $this->run($input);
            if ($r1->isSuccess()) {
                return $r1;
            }

            $r2 = $second->run($input);
            if ($r2->isSuccess()) {
                return $r2;
            }

            $expectation = "({$r1->expected()} or {$r2->expected()})";
            return fail($expectation, "@TODO");
        });
    }

    /**
     * Map the parser into a new object instance
     *
     * @template T2
     *
     * @param class-string<T2> $className
     *
     * @return Parser<T2>
     */
    public function fmapClass(string $className): Parser
    {
        return $this->fmap(
        /** @param mixed $val */
            fn($val) => new $className($val)
        );
    }

    /**
     * Map a function over the parser (which in turn maps it over the result).
     *
     * @template T2
     *
     * @param callable(T) : T2 $transform
     *
     * @return Parser<T2>
     */
    public function fmap(callable $transform): Parser
    {
        return Parser::make(fn(string $input): ParseResult => $this->run($input)->fmap($transform));
    }

    /**
     *
     * Combine the parser with another parser of the same type, which will cause the results to be mappended.
     *
     * @param Parser<T> $other
     *
     * @return Parser<T>
     * @see ParseResult::mappend
     */
    public function mappend(Parser $other): Parser
    {
        return Parser::make(function (string $input) use ($other): ParseResult {
            $r1 = $this->run($input);
            $r2 = $r1->continueOnRemaining($other);
            return $r1->mappend($r2);
        });
    }
}