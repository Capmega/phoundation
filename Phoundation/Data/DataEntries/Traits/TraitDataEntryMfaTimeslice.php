<?php

/**
 * Trait TraitDataEntryMfaTimeslice
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryMfaTimeslice
{
    /**
     * Returns the mfa_timeslice for this object
     *
     * @return int|null
     */
    public function getMfaTimeslice(): ?int
    {
        return $this->getTypesafe('int', 'mfa_timeslice');
    }


    /**
     * Sets the mfa_timeslice for this object
     *
     * @param int|null $mfa_timeslice
     *
     * @return static
     */
    public function setMfaTimeslice(?int $mfa_timeslice): static
    {
        return $this->set(get_null($mfa_timeslice), 'mfa_timeslice');
    }
}
