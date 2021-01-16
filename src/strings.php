<?php declare(strict_types=1);
/**
 * This file is part of the Parsica library.
 *
 * Copyright (c) 2020 Mathias Verraes <mathias@verraes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Verraes\Parsica;

use Verraes\Parsica\Internal\Assert;
use Verraes\Parsica\Internal\EndOfStream;
use Verraes\Parsica\Internal\Fail;
use Verraes\Parsica\Internal\Succeed;
use function Verraes\Parsica\Internal\FP\foldl;

/**
 * Parse a non-empty string.
 *
 * @psalm-return Parser<string>
 * @api
 * @see stringI()
 *
 */
function string(string $str): Parser
{
    Assert::nonEmpty($str);
    $len = mb_strlen($str);
    $label = "'$str'";
    /** @psalm-var Parser<string> $parser */
    $parser = Parser::make($label, function (Stream $input) use ($label, $len, $str): ParseResult {
        try {
            $t = $input->takeN($len);
        } catch (EndOfStream $e) {
            return new Fail($label, $input);
        }
        return $t === $str
            ? new Succeed($str, $input)
            : new Fail($label, $input);
    }
    );
    return $parser;
}

/**
 * Parse a non-empty string, case-insensitive and case-preserving. On success it returns the string cased as the
 * actually parsed input.
 * eg stringI("foobar")->tryString("foObAr") will succeed with "foObAr"
 *
 * @TODO The implementation could be replaced using Stream::takeWhile
 *
 * @psalm-return Parser<string>
 * @api
 * @see string()
 */
function stringI(string $str): Parser
{
    Assert::nonEmpty($str);
    /** @psalm-var list<string> $split */
    $split = mb_str_split($str);
    $chars = array_map(fn(string $c): Parser => charI($c), $split);
    /** @psalm-var Parser<string> $parser */
    $parser = foldl(
        $chars,
        fn(Parser $l, Parser $r): Parser => append($l, $r),
        succeed()
    )->label("'$str'");
    return $parser;
}
