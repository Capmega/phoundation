<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\EntryCore;
use Phoundation\Data\Iterator;
use Stringable;

interface EntryInterface extends CliFormInterface, ArrayableInterface, Stringable
{
    /**
     * Returns all keys that are protected and cannot be removed from this object
     *
     * @return array
     */
    public function getProtectedColumns(): array;

    /**
     * Returns all keys that are protected and cannot be removed from this object
     *
     * @param array $protected_columns
     *
     * @return EntryCore
     */
    public function setProtectedColumns(array $protected_columns): static;

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
     * Loads the specified data into this DataEntry object
     *
     * @param Iterator|array $source
     *
     * @return static
     */
    public function setSource(Iterator|array $source): static;

    /**
     * Generates and display a CLI form for the data in this entry
     *
     * @param string|null $key_header
     * @param string|null $value_header
     *
     * @return static
     */
    public function displayCliForm(?string $key_header = null, ?string $value_header = null): static;
}
