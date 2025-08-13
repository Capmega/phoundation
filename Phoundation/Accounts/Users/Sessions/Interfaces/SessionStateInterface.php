<?php

namespace Phoundation\Accounts\Users\Sessions\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Stringable;

interface SessionStateInterface
{
    /**
     *
     *
     * @param Stringable|string|float|int                    $key
     * @param IteratorInterface|Stringable|array|string|null $pages
     *
     * @return $this
     */
    public function get(Stringable|string|float|int $key, IteratorInterface|Stringable|array|string|null $pages = null): Stringable|string|float|int;


    /**
     *
     *
     * @param Stringable|string|float|int $key
     *
     * @return $this
     */
    public function set(Stringable|string|float|int $value, Stringable|string|float|int $key, Stringable|string|null $page = null): static;
}
