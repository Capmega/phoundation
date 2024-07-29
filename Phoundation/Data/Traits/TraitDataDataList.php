<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;

/**
 * Trait TraitDataDataIterator
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentable.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataDataIterator
{
    /**
     * The data entry
     *
     * @var DataIteratorInterface $data_entry
     */
    protected DataIteratorInterface $data_entry;


    /**
     * Returns the data entry
     *
     * @return DataIteratorInterface
     */
    public function getDataIterator(): DataIteratorInterface
    {
        return $this->data_entry;
    }


    /**
     * Sets the data entry
     *
     * @param DataIteratorInterface $data_entry
     *
     * @return static
     */
    public function setDataIterator(DataIteratorInterface $data_entry): static
    {
        $this->data_entry = $data_entry;

        return $this;
    }
}