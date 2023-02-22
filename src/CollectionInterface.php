<?php

namespace Hyqo\Collection;

use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @template T
 * @template-implements IteratorAggregate<int,T>
 */
interface CollectionInterface extends Countable, IteratorAggregate, JsonSerializable
{
    public function count(): int;

    /**
     * @return Traversable<int,T>
     */
    public function getIterator(): Traversable;

    /**
     * @return array<int,mixed>
     */
    public function jsonSerialize(): array;

    /**
     * @param T $item
     * @return $this
     */
    public function add($item): self;

    /**
     * @return T|null
     */
    public function get(int $index);

    /**
     * @param callable(T, int): void $closure
     * @return $this
     */
    public function each(callable $closure): static;

    /**
     * @param callable(T):(Generator<int,T,mixed,void>|T) $callable
     * @return static<T>
     */
    public function map(callable $callable): static;

    /**
     * @param callable(mixed,T): mixed $callback
     * @param null|mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initial = null): mixed;

    /**
     * @param int $first
     * @param null|int $length
     * @return static
     */
    public function slice(int $first, ?int $length = null): static;

    /**
     * @return static<T>
     */
    public function copy(): static;

    /**
     * @param int $length
     * @return Generator<static<T>>
     */
    public function chunk(int $length): Generator;

    /**
     * @param callable(T): bool $closure
     * @return static<T>
     */
    public function filter(callable $closure): self;

    /**
     * @param null|callable(T):(Generator<array-key,T,mixed,void>|T) $closure
     * @return array<array-key,mixed>
     */
    public function toArray(?callable $closure = null): array;
}
