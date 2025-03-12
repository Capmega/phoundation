<?php

/**
 * Trait TraitDataIterator
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openiterator.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @var IteratorInterface|null $o_iterator
     */
    protected ?IteratorInterface $o_iterator = null;


    /**
     * Returns the iterator
     *
     * @return IteratorInterface|null
     */
    public function getIteratorObject(): ?IteratorInterface
    {
        return $this->o_iterator;
    }


    /**
     * Sets the iterator
     *
     * @param IteratorInterface|null $iterator
     *
     * @return static
     */
    public function setIteratorObject(IteratorInterface|null $iterator = null): static
    {
        $this->o_iterator = $iterator;
        return $this;
    }
}
