<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use ReturnTypeWillChange;

/**
 * Trait TraitDataParent
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataParent
{
    /**
     * @var DataEntryInterface|null $parent
     */
    protected ?DataEntryInterface $parent = null;


    /**
     * Returns the parent
     *
     * @return DataEntryInterface
     */
    #[ReturnTypeWillChange] public function getParent(): DataEntryInterface
    {
        return $this->parent;
    }


    /**
     * Sets the parent
     *
     * @param DataEntryInterface $parent
     *
     * @return static
     */
    public function setParentObject(DataEntryInterface $parent): static
    {
        $this->parent = $parent;

        return $this;
    }
}