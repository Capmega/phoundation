<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataRandomId
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://openrandom_id.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataRandomId
{
    /**
     * Tracks whether to use random id's or not
     *
     * @var bool $random_id
     */
    protected bool $random_id = true;


    /**
     * Returns whether to use random id's or not
     *
     * @return bool
     */
    public function getRandomId(): bool
    {
        return $this->random_id;
    }


    /**
     * Sets whether to use random id's or not
     *
     * @param bool $random_id
     * @return static
     */
    public function setRandomId(bool $random_id): static
    {
        $this->random_id = $random_id;
        return $this;
    }
}