<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

use Iterator;
use Stringable;

interface IteratorBaseInterface extends Iterator, Stringable, ArraySourceInterface
{
    /**
     * Returns the current entry
     *
     * @return mixed
     */
    public function current(): mixed;

    /**
     * Progresses the internal pointer to the next entry
     *
     * @return void
     */
    public function next(): void;

    /**
     * Progresses the internal pointer to the previous entry
     *
     * @return void
     */
    public function previous(): void;

    /**
     * Returns the current key for the current button
     *
     * @return string|int|null
     */
    public function key(): string|int|null;

    /**
     * Returns if the current pointer is valid or not
     *
     * @return bool
     */
    public function valid(): bool;

    /**
     * Rewinds the internal pointer
     *
     * @return void
     */
    public function rewind(): void;

    /**
     * Returns if the list is empty
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Returns if the list is not empty
     *
     * @return bool
     */
    public function isNotEmpty(): bool;
}
