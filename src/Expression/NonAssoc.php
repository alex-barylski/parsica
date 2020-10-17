<?php declare(strict_types=1);
/*
 * This file is part of the Parsica library.
 *
 * Copyright (c) 2020 Mathias Verraes <mathias@verraes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Verraes\Parsica\Expression;

use Verraes\Parsica\Parser;
use function Verraes\Parsica\choice;
use function Verraes\Parsica\collect;

/**
 * @internal
 */
final class NonAssoc implements ExpressionType
{
    private BinaryOperator $operator;

    function __construct(BinaryOperator $operator)
    {
        // @todo throw if $operator->arity() != 2

        $this->operator = $operator;
    }

    public function buildPrecedenceLevel(Parser $previousPrecedenceLevel): Parser
    {
        return choice(
            collect(
                $previousPrecedenceLevel,
                $this->operator->symbol(),
                $previousPrecedenceLevel
            )->map(fn(array $o) => $this->operator->transform()($o[0], $o[2])),
            $previousPrecedenceLevel
        );
    }
}
