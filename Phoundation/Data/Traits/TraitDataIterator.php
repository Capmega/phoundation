<?php

/**
 * Trait TraitDataIterator
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openiterator.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;


trait TraitDataIterator
{
    /**
     *
     *
     * @var IteratorInterface|null $iterator
     */
    protected ?IteratorInterface $iterator = null;


    /**
     * Returns the iterator
     *
     * @return IteratorInterface|null
     */
    public function getIterator(): ?IteratorInterface
    {
        return $this->iterator;
    }


    /**
     * Sets the iterator
     *
     * @param IteratorInterface|null $iterator
     *
     * @return static
     */
    public function setIterator(IteratorInterface|null $iterator = null): static
    {
        if ($iterator) {
            $this->iterator = $iterator;

        } else {
            $this->iterator = null;
        }

        return $this;
    }
}
