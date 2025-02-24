<?php

/**
 * Trait TraitDataMetaColumns
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opencolumn.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Core\Log\Log;

trait TraitDataMetaColumns
{
    /**
     * The meta columns
     *
     * @var array|null $meta_columns
     */
    protected ?array $meta_columns = null;


    /**
     * Returns the meta-columns
     *
     * @return array|null
     */
    public function getMetaColumns(): ?array
    {
        return $this->meta_columns;
    }


    /**
     * Sets the meta-columns
     *
     * @param array|null $columns
     *
     * @return static
     */
    public function setMetaColumns(?array $columns): static
    {
        $this->meta_columns = get_null($columns);
        return $this;
    }


    /**
     * Returns true if this DataEntry class has the specified meta column
     *
     * @param string $column
     *
     * @return bool
     */
    public function hasMetaColumn(string $column): bool
    {
        return in_array($column, $this->meta_columns);
    }
}
