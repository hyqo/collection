<?php

namespace Hyqo\Collection;

use Closure;
use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use ArrayIterator;

/**
 * @template T
 * @template-implements IteratorAggregate<int,T>
 */
class Collection implements Countable, IteratorAggregate, JsonSerializable
{
    protected array $source;

    protected array|Closure $loader;

    /**
     * @param null|array<T> $items
     */
    public function __construct(?array $items = null)
    {
        $this->source = $items ?? [];
        $this->loader = fn() => $this->source;
    }

    /**
     * @param callable():array $loader
     * @return $this
     */
    public function setLoader(callable $loader): static
    {
        $this->loader = $loader;

        return $this;
    }

    protected function &load(): array
    {
        $this->elements ??= array_values(($this->loader)());

        return $this->elements;
    }


    /**
     * @var null|array<int,T>
     */
    protected ?array $elements = null;


    public function count(): int
    {
        return count($this->load());
    }

    /**
     * @return Traversable<int,T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->load());
    }

    /**
     * @return array<int,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->load();
    }

    /**
     * @param T $item
     * @return $this
     */
    public function add($item): self
    {
        $this->load()[] = $item;

        return $this;
    }

    /**
     * @return T|null
     */
    public function get(int $index): mixed
    {
        return $this->load()[$index] ?? null;
    }

    /**
     * @param callable(T, int): void $closure
     * @return $this
     */
    public function each(callable $closure): static
    {
        foreach ($this->load() as $index => $item) {
            $closure($item, $index);
        }

        return $this;
    }

    /**
     * @param callable(T):(Generator<int,T,mixed,void>|T) $callable
     * @return static<T>
     */
    public function map(callable $callable): static
    {
        $collection = new static();

        foreach ($this->load() as $item) {
            $result = $callable($item);

            if ($result instanceof Generator) {
                if ($result->valid()) {
                    foreach ($result as $value) {
                        $collection->add($value);
                    }
                }
            } else {
                $collection->add($result);
            }
        }

        return $collection;
    }

    /**
     * @param callable(mixed,T): mixed $callback
     * @param null|mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->load(), $callback, $initial);
    }

    /**
     * @param int $first
     * @param null|int $length
     * @return static
     */
    public function slice(int $first, ?int $length = null): static
    {
        return new static(array_slice($this->load(), $first, $length));
    }

    /**
     * @return static<T>
     */
    public function copy(): static
    {
        return $this->slice(0);
    }

    /**
     * @param int $length
     * @return Generator<static<T>>
     */
    public function chunk(int $length): Generator
    {
        $count = ceil(count($this) / $length);
        $i = -1;

        while (++$i <= $count - 1) {
            yield $this->slice(($i * $length), $length);
        }
    }

    /**
     * @param callable(T): bool $closure
     * @return static<T>
     */
    public function filter(callable $closure): static
    {
        return new static(array_filter($this->load(), $closure));
    }

    /**
     * @param null|callable(T):(Generator<array-key,T,mixed,void>|T) $closure
     * @return array<array-key,mixed>
     */
    public function toArray(?callable $closure = null): array
    {
        if (null === $closure) {
            return $this->load();
        }

        $array = [];

        foreach ($this->load() as $item) {
            $result = $closure($item);

            if ($result instanceof Generator) {
                if ($result->valid()) {
                    foreach ($result as $key => $value) {
                        $array[$key] = $value;
                    }
                }
            } else {
                $array[] = $result;
            }
        }

        return $array;
    }
}
