<?php

/**
 * Trait TraitDataEntryWorkPhone
 *
 * This trait contains methods for DataEntry objects that require work phone numbers
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryCellPhone
{
    /**
     * Returns the cell phone for this object
     *
     * @return string|null
     */
    public function getCellPhone(): ?string
    {
        return $this->getTypesafe('string', 'cell_phone');
    }


    /**
     * Sets the cell phone for this object
     *
     * @param string|null $cell_phone
     *
     * @return static
     */
    public function setCellPhone(?string $cell_phone): static
    {
        return $this->set(get_null($cell_phone), 'cell_phone');
    }
}
