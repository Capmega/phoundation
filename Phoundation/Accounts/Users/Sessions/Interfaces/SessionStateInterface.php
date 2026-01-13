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
     * @return Stringable|string|float|int
     */
    public function get(Stringable|string|float|int $key, IteratorInterface|Stringable|array|string|null $pages = null): Stringable|string|float|int;


    /**
     *
     *
     * @param Stringable|string|float|int $value
     * @param Stringable|string|float|int $key
     * @param Stringable|string|null      $page
     *
     * @return static
     */
    public function set(Stringable|string|float|int $value, Stringable|string|float|int $key, Stringable|string|null $page = null): static;
}
