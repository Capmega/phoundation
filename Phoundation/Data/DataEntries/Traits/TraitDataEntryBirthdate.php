<?php

/**
 * Trait TraitDataEntryBirthdate
 *
 * This trait contains methods for DataEntry objects that require a birthdate
 *
 * @author    Harrison Macey <harrison@medinet.ca>*
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


use Phoundation\Date\PhoDateTime;

trait TraitDataEntryBirthdate
{
    /**
     * Returns the birthdate day for this object
     *
     * @return PhoDateTime|string|null
     */
    public function getBirthdate(): PhoDateTime|string|null
    {
        return PhoDateTime::new($this->getTypesafe('string', 'birthdate'));
    }


    /**
     * Sets the birthdate day for this object
     *
     * @param PhoDateTime|string|null $birthdate
     *
     * @return static
     */
    public function setBirthdate(PhoDateTime|string|null $birthdate): static
    {
        if ($birthdate instanceof PhoDateTime) {
            $birthdate->format('Y-m-d');
        }

        return $this->set(get_null($birthdate), 'birthdate');
    }
}
