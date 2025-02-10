<?php

/**
 * Trait TraitDataEntryGender
 *
 * This trait contains methods for DataEntry objects that require a gender
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Exception\OutOfBoundsException;


trait TraitDataEntryGender
{
    /**
     * Allowed genders
     *
     * @var array $allowed_genders
     */
    protected array $allowed_genders = [
        ''       => '',
        'male'   => 'male',
        'female' => 'female',
        'other'  => 'other',
        'm'      => 'male',
        'f'      => 'female',
        'M'      => 'male',
        'F'      => 'female',
        'o'      => 'other',
        'x'      => 'other',
    ];


    /**
     * Returns the gender for this object
     *
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->getTypesafe('string', 'gender');
    }


    /**
     * Sets the gender for this object
     *
     * @note This method prefixes each gender with a "#" symbol to ensure that genders are never seen as numeric, which
     *       would cause issues with $identifier detection, as $identifier can be numeric (ALWAYS id) or non numeric
     *       (The other unique column)
     *
     * @param string|null $gender
     *
     * @return static
     */
    public function setGender(?string $gender): static
    {
        if (!array_key_exists($gender, $this->allowed_genders)) {
            throw new OutOfBoundsException(tr('Unknown gender ":gender" specified', [
                ':gender' => $gender,
            ]));
        }

        return $this->set(get_null($gender), 'gender');
    }
}
