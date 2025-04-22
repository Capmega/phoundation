<?php

/**
 * Trait TraitMethodsGetTypesafe
 *
 * @see       \Phoundation\Data\DataEntries\DataEntry
 * @see       \Phoundation\Web\Html\Components\Forms\FilterForm
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitMethodsGetTypesafe {
    /**
     * Returns the value for the specified data key
     *
     * @param string     $type
     * @param string     $column
     * @param mixed|null $default
     *
     * @return mixed
     */
    protected function getTypesafe(string $type, string $column, mixed $default = null): mixed
    {
        return get_safe_typed($type, $this->source, $column, $default);
    }
}