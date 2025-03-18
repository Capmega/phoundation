<?php

/**
 * Trait TraitDataEntryMfaCode
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


trait TraitDataEntryMfaCode
{
    /**
     * Returns the mfa_code for this object
     *
     * @return string|null
     */
    public function getMfaCode(): ?string
    {
        return $this->getTypesafe('string', 'mfa_code');
    }


    /**
     * Sets the mfa_code for this object
     *
     * @param string|null $mfa_code
     *
     * @return static
     */
    public function setMfaCode(?string $mfa_code): static
    {
        return $this->set(get_null($mfa_code), 'mfa_code');
    }
}
