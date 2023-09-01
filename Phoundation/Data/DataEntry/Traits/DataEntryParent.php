<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


/**
 * Trait DataEntryParent
 *
 * This trait contains methods for DataEntry objects that require a company
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryParent
{
    /**
     * Returns the parents_id for this object
     *
     * @return int|null
     */
    public function getParentsId(): ?int
    {
        return $this->getSourceValue('int', 'parents_id');
    }


    /**
     * Sets the parents_id for this object
     *
     * @param int|null $parents_id
     * @return static
     */
    public function setParentsId(?int $parents_id): static
    {
        return $this->setSourceValue('parents_id', $parents_id);
    }


    /**
     * Returns the parents_name for this object
     *
     * @return string|null
     */
    public function getParentsName(): ?string
    {
        return $this->getSourceValue('string', 'parents_name');
    }


    /**
     * Returns the parents_id for this object
     *
     * @return DataEntryInterface|null
     */
    public function getParent(): ?DataEntryInterface
    {
        $parents_id = $this->getSourceValue('int', 'parents_id');

        if ($parents_id) {
            return new static($parents_id);
        }

        return null;
    }


    /**
     * Sets the parents_name for this object
     *
     * @param string|null $parents_name
     * @return static
     */
    public function setParentsName(?string $parents_name): static
    {
        return $this->setSourceValue('parents_name', $parents_name);
    }
}