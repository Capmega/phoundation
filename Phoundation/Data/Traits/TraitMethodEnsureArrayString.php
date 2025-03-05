<?php

/**
 * Trait TraitMethodEnsureArrays
 *
 * This trait contains the (protected) methods ::ensureArrays() and ::ensureArray() which ensure that the source values
 * contain only arrays
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\ArraySourceInterface;
use Phoundation\Exception\OutOfBoundsException;
use ReturnTypeWillChange;


trait TraitMethodEnsureArrayString
{
    use TraitDataSourceArray;


    /**
     * Ensures that all iterator entries are arrays
     *
     * @return static
     */
    protected function ensureArrayStrings(): static
    {
        foreach ($this->source as $key => &$value) {
            $value = $this->ensureArrayString($key);
        }

        unset($value);
        return $this;
    }


    /**
     * Ensure the entry we're going to return is from DataEntryInterface interface
     *
     * @param string|float|int $key
     * @param bool             $allow_scalar
     *
     * @return array|string|float|int|bool|null
     */
    #[ReturnTypeWillChange] protected function ensureArrayString(string|float|int $key, bool $allow_scalar = true): array|string|float|int|bool|null
    {
        // Arrays
        if (is_array($this->source[$key])) {
            // Already array
            return $this->source[$key];
        }

        // Strings, floats, integers, true
        if (is_scalar($this->source[$key])) {
            if ($allow_scalar) {
                // This is good too
                return $this->source[$key];
            }

            return [$this->source[$key]];
        }

        // Objects
        if (is_a($this->source[$key], ArraySourceInterface::class, true)) {
            // Can only do this for objects that have ArraySourceInterface so that we can dump array sources in them.
            return $this->source[$key]->__toArray();
        }

        // 0, 0.0, false, "", NULL
        if (empty($this->source[$key])) {
            if ($allow_scalar) {
                // This is good too
                return $this->source[$key];
            }

            return [$this->source[$key]];
        }

        throw new OutOfBoundsException(tr('Cannot convert source key ":key" to array, the value ":value" cannot be converted', [
            ':key'   => $key,
            ':value' => $this->source[$key]
        ]));
    }
}