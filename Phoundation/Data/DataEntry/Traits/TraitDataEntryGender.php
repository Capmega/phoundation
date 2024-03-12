<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait TraitDataEntryGender
 *
 * This trait contains methods for DataEntry objects that require a gender
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataEntryGender
{
    protected array $translate = [
        ''       => '',
        'male'   => 'male',
        'female' => 'female',
        'other'  => 'other',
        'm'      => 'male',
        'f'      => 'female',
        'o'      => 'other',
        'x'      => 'other'
    ];

    /**
     * Returns the gender for this object
     *
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'gender');
    }


    /**
     * Sets the gender for this object
     *
     * @note This method prefixes each gender with a "#" symbol to ensure that genders are never seen as numeric, which
     *       would cause issues with $identifier detection, as $identifier can be numeric (ALWAYS id) or non numeric
     *       (The other unique column)
     * @param string|null $gender
     * @return static
     */
    public function setGender(?string $gender): static
    {
        if (!array_key_exists($gender, $this->translate)) {
            throw new OutOfBoundsException(tr('Unknown gender ":gender" specified', [
                ':gender' => $gender
            ]));
        }

        return $this->setSourceValue('gender', get_null($gender));
    }
}
