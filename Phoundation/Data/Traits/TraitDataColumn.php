<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataColumn
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opencolumn.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataColumn
{
    /**
     * The column to use
     *
     * @var string|null $column
     */
    protected ?string $column;


    /**
     * Returns the column
     *
     * @return string|null
     */
    public function getColumn(): ?string
    {
        return $this->column;
    }


    /**
     * Sets the column
     *
     * @param string|null $column
     *
     * @return static
     */
    public function setColumn(?string $column): static
    {
        $this->column = $column;
        return $this;
    }
}
