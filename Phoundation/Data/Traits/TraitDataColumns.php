<?php

/**
 * Trait TraitDataColumns
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentitle.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Utils\Arrays;


trait TraitDataColumns
{
    /**
     * Tracks columns
     *
     * @var array|null $columns
     */
    protected ?array $columns = null;


    /**
     * Returns the columns
     *
     * @return array|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }


    /**
     * Sets the columns
     *
     * @param ArrayableInterface|array|string|null $columns
     *
     * @return static
     */
    public function setColumns(ArrayableInterface|array|string|null $columns): static
    {
        $this->columns = get_null(Arrays::force($columns));
        return $this;
    }
}
