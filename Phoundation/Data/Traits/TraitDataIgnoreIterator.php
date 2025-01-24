<?php

/**
 * Trait TraitDataIgnoreIterator
 *
 * This trait contains the basic methods required to use an ignore iterator list
 *
 * The ignore list can be used to track a list of items that should be ignored
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openignore.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;


trait TraitDataIgnoreIterator
{
    /**
     * The list of items to ignore
     *
     * @var IteratorInterface|null $ignore
     */
    protected ?IteratorInterface $ignore = null;


    /**
     * Returns the ignore list
     *
     * @param bool $force
     *
     * @return IteratorInterface|null
     */
    public function getIgnoreObject(bool $force = false): ?IteratorInterface
    {
        if (empty($this->ignore) and ($force === true)) {
            $this->ignore = new Iterator();
        }

        return $this->ignore;
    }


    /**
     * Sets the ignore list
     *
     * @param IteratorInterface|null $ignore
     *
     * @return static
     */
    public function setIgnoreObject(?IteratorInterface $ignore): static
    {
        $this->ignore = $ignore;
        return $this;
    }
}
