<?php

/**
 * Trait TraitDataArrayData
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


trait TraitDataArrayData
{
    /**
     * The data for this object
     *
     * @var array $data
     */
    protected array $data = [];


    /**
     * Returns the data
     *
     * @return array
     */
    public function getData(): array
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
        return $this->clearData()
                    ->addData(get_null($data));
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
        if (($key === null) and ($value === null)) {
            return $this;
        }

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
