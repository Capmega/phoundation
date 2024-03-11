<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Interfaces\DataListInterface;


/**
 * Trait DataDataList
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opentable.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataDataList
{
    /**
     * The data entry
     *
     * @var DataListInterface $data_entry
     */
    protected DataListInterface $data_entry;


    /**
     * Returns the data entry
     *
     * @return DataListInterface
     */
    public function getDataList(): DataListInterface
    {
        return $this->data_entry;
    }


    /**
     * Sets the data entry
     *
     * @param DataListInterface $data_entry
     * @return static
     */
    public function setDataList(DataListInterface $data_entry): static
    {
        $this->data_entry = $data_entry;
        return $this;
    }
}