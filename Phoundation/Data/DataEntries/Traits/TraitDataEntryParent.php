<?php

/**
 * Trait TraitDataEntryParent
 *
 * This trait contains methods for DataEntry objects that require a company
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Exception\UnderConstructionException;


trait TraitDataEntryParent
{
    /**
     * Cache for the clinician data
     *
     * @var DataEntryInterface|null $_parent
     */
    protected ?DataEntryInterface $_parent = null;


    /**
     * Returns the parents_id for this object
     *
     * @return int|null
     */
    public function getParentsId(): ?int
    {
        return $this->getTypesafe('int', 'parents_id');
    }


    /**
     * Sets the parents_id for this object
     *
     * @param int|null $id
     *
     * @return static
     */
    public function setParentsId(?int $id): static
    {
        if ($this->_parent?->getId() === $id) {
            return $this;
        }

        return $this->set($id, 'parents_id');
    }


    /**
     * Returns the parents_name for this object
     *
     * @return string|null
     */
    public function getParentsName(): ?string
    {
        return $this->getTypesafe('string', 'parents_name');
    }


    /**
     * Sets the parents_name for this object
     *
     * @param string|null $name
     *
     * @return static
     */
    public function setParentsName(?string $name): static
    {
        if ($this->_parent?->getName() === $name) {
            return $this;
        }
throw new UnderConstructionException('Add support for expected parents type and then implement setParentsData method here');
        return $this->set($name, 'parents_name');
    }


    /**
     * Returns the parent DataEntry object for this object
     *
     * @return DataEntryInterface|null
     */
    public function getParent(): ?DataEntryInterface
    {
        return $this->_parent;
    }


    /**
     * Sets the parent DataEntry object for this object
     *
     * @param DataEntryInterface|null $_parent
     *
     * @return static
     */
    public function aetParent(?DataEntryInterface $_parent): static
    {
        return $this->setParentData($_parent);
    }


    /**
     * Sets the clinician ID, Practitioner Number, and Email
     *
     * @param DataEntryInterface|null $_parent
     *
     * @return static
     */
    protected function setParentData(?DataEntryInterface $_parent): static
    {
        $this->_parent = $_parent;

        return $this->set($_parent?->getId(false), 'parents_id')
                    ->set($_parent?->getName()   , 'parents_name');
    }
}
