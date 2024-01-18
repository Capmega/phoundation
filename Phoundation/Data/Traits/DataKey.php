<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataKey
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://openkey.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataKey
{
    /**
     * The key to use
     *
     * @var string|null $key
     */
    protected ?string $key;


    /**
     * Returns the key
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }


    /**
     * Sets the key
     *
     * @param string|null $key
     * @return static
     */
    public function setKey(?string $key): static
    {
        $this->key = $key;
        return $this;
    }
}