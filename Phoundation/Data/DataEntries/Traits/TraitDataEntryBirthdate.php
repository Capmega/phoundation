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
use Phoundation\Exception\OutOfBoundsException;

trait TraitDataEntryBirthdate
{
    /**
     * Returns the birthdate day for this object
     *
     * @return string|null
     */
    public function getBirthdate(): string|null
    {
        return $this->getTypesafe('string', 'birthdate');
    }


    /**
     * Sets the birthdate day for this object
     *
     * @param PhoDateTime|array|string|null $birthdate
     *
     * @return static
     */
    public function setBirthdate(PhoDateTime|array|string|null $birthdate): static
    {
        if (is_array($birthdate)) {
            try {
                $birthdate = array_get_safe($birthdate, 'date');

            } catch (\Throwable $e) {
                throw OutOfBoundsException::new(tr('Unknown array given, unable to set birthdate'))
                                          ->addData([
                                              'array' => $birthdate,
                                              'exception' => $e
                                          ]);
            }
        }

        if ($birthdate instanceof PhoDateTime) {
            $birthdate->format('Y-m-d');
        }

        return $this->set(get_null($birthdate), 'birthdate');
    }


    /**
     * Returns the Birthdate as a PhoDateTime Object
     *
     * @return PhoDateTime|null
     */
    public function getBirthdateObject(): PhoDateTime|null
    {
        $birthdate = $this->getBirthdate();
        if ($birthdate) {
            return PhoDateTime::new($birthdate);
        }

        return null;
    }


    /**
     * Sets the birthdate by date object
     *
     * @param PhoDateTime|null $birthdate
     *
     * @return static
     */
    public function setBirthdateObject(PhoDateTime|null $birthdate): static
    {
        return $this->set(get_null($birthdate), 'birthdate');
    }
}
