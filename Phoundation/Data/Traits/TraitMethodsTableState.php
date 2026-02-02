<?php

/**
 * Trait TraitMethodProtectedSetTableState
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Utils\Strings;


trait TraitMethodsTableState
{
    /**
     * Returns the state for the table for this DataIterator
     *
     * @return string|null
     */
    public function getTableState(): ?string
    {
        if (static::getTable()) {
            $return = cache('values')->getOrGenerate($this->connector . '-table-state-' . static::getTable());

            if (empty($return)) {
                // Initialize a state for this table
                $return = $this->setTableState();
            }

            return $return;
        }

        return null;
    }


    /**
     * Updates the state for the table for this data entry
     *
     * @return string|null
     */
    protected function setTableState(): ?string
    {
        $state = Strings::getUuid();

        cache('dataentries')->set($state, $this->connector . '-table-state-' . static::getTable());

        return $state;
    }
}
