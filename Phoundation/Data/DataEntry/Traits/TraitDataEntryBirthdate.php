<?php

/**
 * Trait TraitDataEntryBirthdate
 *
 * This trait contains methods for DataEntry objects that require a birthdate
 *
 * @author    Harrison Macey <harrison@medinet.ca>*
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


use Phoundation\Date\PhoDate;

trait TraitDataEntryBirthdate
{
    /**
     * Returns the birthdate day for this object
     *
     * @return PhoDate|null
     */
    public function getBirthdate(): ?PhoDate
    {
        return $this->getTypesafe('date', 'birthdate');
    }


    /**
     * Sets the birthdate day for this object
     *
     * @param PhoDate|null $birthdate
     *
     * @return static
     */
    public function setBirthdate(?PhoDate $birthdate): static
    {
        return $this->set($birthdate, 'birthdate');
    }
}
