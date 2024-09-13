<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

use Phoundation\Data\EntryCore;
use Stringable;


interface EntryInterface extends ArraySourceInterface, CliFormInterface, Stringable
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
     * Generates and display a CLI form for the data in this entry
     *
     * @param string|null $key_header
     * @param string|null $value_header
     *
     * @return static
     */
    public function displayCliForm(?string $key_header = null, ?string $value_header = null): static;

    /**
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     * @return array
     */
    public function get(string $key): mixed;

    /**
     * Sets the value for the specified data key
     *
     * @param mixed $value
     * @param string $column
     * @param bool   $force
     *
     * @return static
     */
    public function set(mixed $value, string $column, bool $force = false): static;
}
