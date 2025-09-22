<?php

/**
 * Class EntryCore
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data;

use Phoundation\Cli\Cli;
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Data\Traits\TraitDataArraySource;
use Phoundation\Data\Traits\TraitDataExceptionOnGet;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use ReturnTypeWillChange;
use Stringable;


class EntryCore implements EntryInterface
{
    use TraitDataArraySource;
    use TraitDataExceptionOnGet;


    /**
     * Default protected keys, keys that may not leave this object
     *
     * @var array
     */
    protected array $protected_columns = ['password'];


    /**
     * Returns all keys that are protected and cannot be removed from this object
     *
     * @return array
     */
    public function getProtectedColumns(): array
    {
        return $this->protected_columns;
    }


    /**
     * Returns all keys that are protected and cannot be removed from this object
     *
     * @param array $protected_columns
     *
     * @return EntryCore
     */
    public function setProtectedColumns(array $protected_columns): static
    {
        $this->protected_columns = $protected_columns;
        return $this;
    }


    /**
     * Adds a single extra column that is protected and cannot be removed or accessed directly from this object
     *
     * @param string $key
     *
     * @return static
     */
    protected function addProtectedColumn(string $key): static
    {
        $this->protected_columns[] = $key;

        return $this;
    }


    /**
     * Returns all source data for this Entry object
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @return array
     */
    public function getSource(): array
    {
        return Arrays::removeKeys($this->source, $this->protected_columns);
    }


    /**
     * Generates and display a CLI form for the data in this entry
     *
     * @param string|null $key_header
     * @param string|null $value_header
     *
     * @return static
     */
    public function displayCliForm(?string $key_header = null, ?string $value_header = null): static
    {
        Cli::displayForm($this->source, $key_header, $value_header);

        return $this;
    }


    /**
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return array
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): mixed
    {
        return array_get_safe($this->source, $key, $default, $exception ?? $this->exception_on_get);
    }


    /**
     * Sets the value for the specified data key
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     * @param bool                        $skip_null_values
     *
     * @return static
     */
    public function set(mixed $value, Stringable|string|float|int $key, bool $skip_null_values = false): static
    {
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        $this->source[$key] = $value;
        return $this;
    }
}
