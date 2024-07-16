<?php

/**
 * Trait TraitDataTable
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentable.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataTable
{
    /**
     * The table to use
     *
     * @var string|null $table
     */
    protected ?string $table = null;


    /**
     * Returns the table
     *
     * @return string|null
     */
    public function getTable(): ?string
    {
        return $this->table;
    }


    /**
     * Sets the table
     *
     * @param string|null $table
     *
     * @return static
     */
    public function setTable(?string $table): static
    {
        $this->table = $table;

        return $this;
    }
}