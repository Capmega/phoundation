<?php

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

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;

trait TraitDataDataIterator
{
    /**
     * The data entry
     *
     * @var DataIteratorInterface $data_iterator
     */
    protected DataIteratorInterface $data_iterator;


    /**
     * Returns the data entry
     *
     * @return DataIteratorInterface
     */
    public function getDataIterator(): DataIteratorInterface
    {
        return $this->data_iterator;
    }


    /**
     * Sets the data entry
     *
     * @param DataIteratorInterface $data_iterator
     *
     * @return static
     */
    public function setDataIterator(DataIteratorInterface $data_iterator): static
    {
        $this->data_iterator = $data_iterator;

        return $this;
    }
}