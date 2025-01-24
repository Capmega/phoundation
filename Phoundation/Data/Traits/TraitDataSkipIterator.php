<?php

/**
 * Trait TraitDataSkipIterator
 *
 * This trait contains the basic methods required to use a skip iterator list
 *
 * The skip list can be used to track a list of items that should be skipped
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openskip.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;

trait TraitDataSkipIterator
{
    /**
     * The list of items to skip
     *
     * @var IteratorInterface|null $skip
     */
    protected ?IteratorInterface $skip = null;


    /**
     * Returns the skip list
     *
     * @param bool $force
     *
     * @return IteratorInterface|null
     */
    public function getSkipObject(bool $force = false): ?IteratorInterface
    {
        if (empty($this->skip) and ($force === true)) {
            $this->skip = new Iterator();
        }

        return $this->skip;
    }


    /**
     * Sets the skip list
     *
     * @param IteratorInterface|null $skip
     *
     * @return static
     */
    public function setSkipObject(?IteratorInterface $skip): static
    {
        $this->skip = $skip;
        return $this;
    }
}
