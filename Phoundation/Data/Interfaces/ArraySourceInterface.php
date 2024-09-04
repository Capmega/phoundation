<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

use Countable;
use PDOStatement;
use Phoundation\Core\Interfaces\ArrayableInterface;


interface ArraySourceInterface extends ArrayableInterface, Countable
{
    /**
     * Returns all data for this data entry at once with an array of information
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
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
     * Loads the specified data into this DataEntry object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
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
}
