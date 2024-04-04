<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


/**
 * Trait TraitDataDataEntry
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentable.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataDataEntry
{
    /**
     * The data entry
     *
     * @var DataEntryInterface $data_entry
     */
    protected DataEntryInterface $data_entry;


    /**
     * Returns the data entry
     *
     * @return DataEntryInterface
     */
    public function getDataEntry(): DataEntryInterface
    {
        return $this->data_entry;
    }


    /**
     * Sets the data entry
     *
     * @param DataEntryInterface $data_entry
     *
     * @return static
     */
    public function setDataEntry(DataEntryInterface $data_entry): static
    {
        $this->data_entry = $data_entry;
        return $this;
    }
}