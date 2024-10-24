<?php

/**
 * Trait TraitDataRestrictions
 *
 * This adds filesystem restrictions to objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;

trait TraitDataEntryRestrictions
{
    use TraitDataRestrictions {
        setRestrictions as protected __setRestrictions;
    }


    /**
     * Returns access restrictions for this task
     *
     * @return PhoRestrictions
     */
    public function getRestrictions(): PhoRestrictionsInterface
    {
        return PhoRestrictions::newFromImport($this->getTypesafe('string', 'restrictions'));
    }


    /**
     * Sets access restrictions for this task
     *
     * @param PhoRestrictionsInterface|array|string|null $restrictions
     * @param bool                                       $write
     * @param string|null                                $label
     *
     * @return static
     */
    public function setRestrictions(PhoRestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): static
    {
        if (!$restrictions instanceof PhoRestrictionsInterface) {
            $restrictions = PhoRestrictions::newFromImport($restrictions);
        }

        return $this->set($restrictions->exportToString(), 'restrictions')
                    ->__setRestrictions($restrictions);
    }
}
