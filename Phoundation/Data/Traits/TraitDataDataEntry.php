<?php

/**
 * Trait TraitDataDataEntry
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentable.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;


trait TraitDataDataEntry
{
    /**
     * The data entry
     *
     * @var DataEntryInterface|null $_data_entry
     */
    protected ?DataEntryInterface $_data_entry = null;


    /**
     * Returns the data entry
     *
     * @return DataEntryInterface|null
     */
    public function getDataEntryObject(): ?DataEntryInterface
    {
        return $this->_data_entry;
    }


    /**
     * Sets the data entry
     *
     * @param DataEntryInterface|null $_data_entry
     *
     * @return static
     */
    public function setDataEntryObject(?DataEntryInterface $_data_entry): static
    {
        $this->_data_entry = $_data_entry;
        return $this;
    }
}
