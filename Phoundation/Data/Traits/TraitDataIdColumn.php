<?php

/**
 * Trait TraitDataIdColumn
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openid_column.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataIdColumn
{
    /**
     * The id_column to use
     *
     * @var string|null $id_column
     */
    protected ?string $id_column = 'id';


    /**
     * Returns the id_column
     *
     * @return string|null
     */
    public function getIdColumn(): ?string
    {
        return $this->id_column;
    }


    /**
     * Sets the id_column
     *
     * @param string|null $id_column
     *
     * @return static
     */
    public function setIdColumn(?string $id_column): static
    {
        $this->id_column = $id_column;

        return $this;
    }
}