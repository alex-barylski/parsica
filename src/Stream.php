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

use Verraes\Parsica\Internal\NovelImmutablePosition;

/**
 * Represents an input stream. This allows us to have different types of input, each with their own optimizations.
 *
 * @psalm-immutable
 */
interface Stream
{
    /**
     * Extract a single token from the stream. Throw if the stream is empty.
     *
     * @throw EndOfStream
     */
    public function take1(): string;

    /**
     * Try to extract a chunk of length $n, or if the stream is too short, the rest of the stream.
     *
     * Valid implementation should follow the rules:
     *
     * 1. If the requested length <= 0, the empty token and the original stream should be returned.
     * 2. If the requested length > 0 and the stream is empty, throw EndOfStream.
     * 3. In other cases, take a chunk of length $n (or shorter if the stream is not long enough) from the input stream
     * and return the chunk along with the rest of the stream.
     *
     * @throw EndOfStream
     */
    public function takeN(int $n): string;


    /**
     * Extract a chunk of the stream, by taking tokens as long as the predicate holds. Return the chunk and the rest of
     * the stream.
     *
     * @TODO This method isn't strictly necessary but let's see.
     *
     * @psalm-param callable(string):bool $predicate
     */
    public function takeWhile(callable $predicate) : string;

    /**
     * @deprecated We will need to get rid of this again at some point, we can't assume all streams will be strings
     * @todo remove
     */
    public function __toString(): string;

    /**
     * Test if the stream is at its end.
     */
    public function isEOF(): bool;

    /**
     * The position of the parser in the stream.
     *
     * @internal
     */
    public function position() : NovelImmutablePosition;

    public function rollback(): void;

    public function beginTransaction(): void;

    public function commit(): void;

    public function filename(): string;

    /**
     * Read the previous character in the stream
     */
    public function peakBack() : string;

    /**
     * Read the next n tokens without advancing the stream pointer
     */
    public function peakWhile(callable $predicate): string;

    /**
     * Read the next n tokens without advancing the stream pointer
     */
    public function peakN(int $n): string;

    /**
     * Read the next token without advancing the stream pointer, or return the empty string
     */
    public function peak1(): string;

}
