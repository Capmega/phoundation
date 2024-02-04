<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryVerifiedOn
 *
 * This trait contains methods for DataEntry objects that require a verification date
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryVerifiedOn
{
    /**
     * Returns the verified_on for this user
     *
     * @return string|null
     */
    public function getVerifiedOn(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'verified_on');
    }


    /**
     * Sets the verified_on for this user
     *
     * @param string|null $verified_on
     * @return static
     */
    public function setVerifiedOn(?string $verified_on): static
    {
        return $this->setSourceValue('verified_on', $verified_on);
    }
}
