<?php

namespace Hyqo\Collection\Test\Fixtures;

class Item
{
    public function __construct(
        public string $title,
        public int $amount,
    ) {
    }
}
