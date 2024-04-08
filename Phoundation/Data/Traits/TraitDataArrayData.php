<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

/**
 * Trait TraitDataArrayData
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataArrayData
{
    /**
     * The data for this object
     *
     * @var array|null $data
     */
    protected ?array $data = null;


    /**
     * Returns the data
     *
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }


    /**
     * Sets the data
     *
     * @param array|null $data
     *
     * @return static
     */
    public function setData(?array $data): static
    {
        $this->data = [];

        return $this->addData($data);
    }


    /**
     * Adds the specified data
     *
     * @param array|string|null $key
     * @param string|int|null   $value
     *
     * @return static
     */
    public function addData(array|string|null $key, string|int|null $value = null): static
    {
        if (is_array($key)) {
            $data = $key;
            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    $value = explode('=', $value);
                    $this->addData(isset_get($value[0]), isset_get($value[1]));

                } else {
                    $this->addData($key, $value);
                }
            }

        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }


    /**
     * Clears the data
     *
     * @return static
     */
    public function clearData(): static
    {
        $this->data = [];

        return $this;
    }
}
