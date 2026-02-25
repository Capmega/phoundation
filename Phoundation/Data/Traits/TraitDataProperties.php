<?php

/**
 * Trait TraitDataProperties
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Utils\Arrays;


trait TraitDataProperties
{
    /**
     * Tracks the properties array
     *
     * @var array|null
     */
    protected ?array $properties = null;


    /**
     * Returns the properties array
     *
     * @return array
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }


    /**
     * Returns the value for the requested property key, or NULL if it does not exist
     *
     * @param string|float|int $key
     * @param mixed|null       $default
     *
     * @return mixed
     */
    public function getProperty(string|float|int $key, mixed $default = null): mixed
    {
        if (is_array($this->properties)) {
            if (array_key_exists($key, $this->properties)) {
                $return = $this->properties[$key];

                if (is_callable($return)) {
                    return $return();
                }

                return $return;
            }
        }

        return $default;
    }


    /**
     * Returns the value for the requested property key, or NULL if it does not exist
     *
     * @param string|float|int $key
     * @param mixed|null       $default
     *
     * @return mixed
     */
    public function getPropertyBoolean(string|float|int $key, mixed $default = false): bool
    {
        return $this->getProperty($key, $default);
    }


    /**
     * Sets the value for the requested property key
     *
     * @param mixed            $value
     * @param string|float|int $key
     *
     * @return mixed
     */
    public function addProperty(mixed $value, string|float|int $key): static
    {
        if ($this->properties === null) {
            // Initialize the properties array
            $this->properties = [];
        }

        $this->properties[$key] = $value;
        return $this;
    }


    /**
     * Sets the properties array
     *
     * @param IteratorInterface|array|null $properties
     *
     * @return static
     */
    public function setProperties(IteratorInterface|array|null $properties): static
    {
        $this->properties = get_null(Arrays::force($properties));
        return $this;
    }
}
