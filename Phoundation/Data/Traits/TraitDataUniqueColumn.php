<?php

/**
 * Trait TraitDataUniqueColumn
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openunique_column.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataUniqueColumn
{
    /**
     * The unique_column to use
     *
     * @var string|null $unique_column
     */
    protected ?string $unique_column = null;


    /**
     * Returns the unique_column
     *
     * @return string|null
     */
    public function getUniqueColumn(): ?string
    {
        return $this->unique_column;
    }


    /**
     * Sets the unique_column
     *
     * @param string|null $unique_column
     *
     * @return static
     */
    public function setUniqueColumn(?string $unique_column): static
    {
        $this->unique_column = $unique_column;

        return $this;
    }
}