<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

use Countable;
use PDOStatement;
use Phoundation\Core\Interfaces\ArrayableInterface;
use ReturnTypeWillChange;
use Stringable;


interface ArraySourceInterface extends ArrayableInterface, Countable
{
    /**
     * Returns the source data when cast to array
     *
     * @return array
     */
    public function __toArray(): array;

    /**
     * Returns the source
     *
     * @return array
     */
    public function getSource(): array;

    /**
     * Returns a list of all internal definition keys
     *
     * @return mixed
     */
    public function getSourceKeys(): array;

    /**
     * Sets the source
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null
     * @param array|null                                       $execute
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static;

    /**
     * Returns the number of items contained in this object
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Returns the number of items contained in this object
     *
     * Wrapper for IteratorCore::getCount()
     *
     * @return int
     */
    public function count(): int;

    /**
     * Clears all the internal content for this object
     *
     * @return static
     */
    public function clear(): static;

    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = true): mixed;

    /**
     * Sets the value for the specified key
     *
     * @note this is basically a wrapper function for IteratorCore::add($value, $key, false) that always requires a key
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     *
     * @return mixed
     */
    public function set(mixed $value, Stringable|string|float|int $key): static;

    /**
     * Returns the random entry
     *
     * @return Stringable|string|int|null
     */
    #[ReturnTypeWillChange] public function getRandomKey(): Stringable|string|int|null;

    /**
     * Returns a random entry
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getRandom(): mixed;
}
