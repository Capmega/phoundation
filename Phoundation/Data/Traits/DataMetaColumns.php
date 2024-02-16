<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Data\DataEntry\DataEntry;

/**
 * Trait DataMetaColumns
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opencolumn.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataMetaColumns
{
    /**
     * The meta columns
     *
     * @var array $meta_columns
     */
    protected array $meta_columns;


    /**
     * Returns the meta-columns
     *
     * @return array
     */
    public function getMetaColumns(): array
    {
        return $this->meta_columns;
    }


    /**
     * Sets the meta-columns
     *
     * @param array $columns
     * @return static
     */
    public function setMetaColumns(array $columns): static
    {
        $this->meta_columns = $columns;
        return $this;
    }
}
