<?php

/**
 * Trait TraitDataDataIterator
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

use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;


trait TraitDataDataIterator
{
    /**
     * The data entry
     *
     * @var DataIteratorInterface|null $_data_iterator
     */
    protected ?DataIteratorInterface $_data_iterator = null;


    /**
     * Returns the DataIterator object
     *
     * @return DataIteratorInterface|null
     */
    public function getDataIteratorObject(): ?DataIteratorInterface
    {
        return $this->_data_iterator;
    }


    /**
     * Sets the DataIterator object
     *
     * @param DataIteratorInterface|null $_data_iterator
     *
     * @return static
     */
    public function setDataIteratorObject(?DataIteratorInterface $_data_iterator): static
    {
        $this->_data_iterator = $_data_iterator;
        return $this;
    }
}
