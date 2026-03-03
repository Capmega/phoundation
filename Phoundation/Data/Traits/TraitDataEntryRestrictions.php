<?php

/**
 * Trait TraitDataRestrictions
 *
 * This adds filesystem restrictions to objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;


trait TraitDataEntryRestrictions
{
    use TraitDataRestrictions {
        setRestrictionsObject as protected __setRestrictionsObject;
    }


    /**
     * Returns access restrictions for this task
     *
     * @return PhoRestrictions
     */
    public function getRestrictionsObject(): PhoRestrictionsInterface
    {
        return PhoRestrictions::newFromImport($this->getTypesafe('string', 'restrictions'));
    }


    /**
     * Sets access restrictions for this task
     *
     * @param PhoRestrictionsInterface|array|string|null $_restrictions
     * @param bool                                       $write
     * @param string|null                                $label
     *
     * @return static
     */
    public function setRestrictionsObject(PhoRestrictionsInterface|array|string|null $_restrictions = null, bool $write = false, ?string $label = null): static
    {
        if ($_restrictions) {
            if (!$_restrictions instanceof PhoRestrictionsInterface) {
                $_restrictions = PhoRestrictions::newFromImport($_restrictions);
            }
        }

        return $this->set($_restrictions?->getPoadString(), 'restrictions')
                    ->__setRestrictionsObject($_restrictions);
    }


    /**
     * Returns the restrictions for this object as a string
     *
     * @return string|null
     */
    public function getRestrictions(): ?string
    {
        return $this->getTypesafe('string', 'restrictions');
    }


    /**
     * Sets the restrictions column for this object
     *
     * @param string|null $restrictions
     *
     * @return static
     */
    public function setRestrictions(string|null $restrictions = null): static
    {
        return $this->setRestrictionsObject($restrictions);
    }
}
