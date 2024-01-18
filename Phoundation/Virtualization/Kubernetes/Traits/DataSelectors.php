<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Traits;


/**
 * Trait DataSelector
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://openselector.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataSelectors
{
    /**
     * Selectors
     *
     * @var array $selectors
     */
    protected array $selectors = [];

    /**
     * Returns the selectors
     *
     * @return array|null
     */
    public function getSelectors(): ?array
    {
        return $this->selectors;
    }


    /**
     * Clears the selectors
     *
     * @return static
     */
    public function clearSelector(): static
    {
        $this->selectors = [];
        return $this;
    }


    /**
     * Sets the selectors
     *
     * @param array|null $selectors
     * @return static
     */
    public function setSelectors(?array $selectors): static
    {
        $this->selectors = [];
        return $this->addSelectors($selectors);
    }


    /**
     * Adds the specified selectors
     *
     * @param array|null $selectors
     * @return static
     */
    public function addSelectors(?array $selectors): static
    {
        foreach ($selectors as $key => $value) {
            if (is_numeric($key)) {
                $value = explode('=', $value);
                $this->addSelector(isset_get($value[0]), isset_get($value[1]));

            } else {
                $this->addSelector($key, $value);
            }
        }

        return $this;
    }


    /**
     * Adds the specified selector
     *
     * @param string $key
     * @param string|int $value
     * @return static
     */
    public function addSelector(string $key, string|int $value): static
    {
        $this->selectors[$key] = $value;
        return $this;
    }
}
