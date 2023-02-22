<?php

namespace Hyqo\Collection;

/**
 * @template T
 * @param array<T> $items
 * @return Collection<T>
 */
function collect(array $items): Collection
{
    return new Collection($items);
}
