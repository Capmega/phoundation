<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Strings;


/**
 * Trait DataEntryPhone
 *
 * This trait contains methods for DataEntry objects that require phone numbers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryPhone
{
    /**
     * Returns the phone for this object
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->getDataValue('string', 'phone');
    }


    /**
     * Sets the phone for this object
     *
     * @param array|string|null $phone
     * @return static
     */
    public function setPhone(array|string|null $phone): static
    {
        return $this->setDataValue('phone', Strings::force($phone));
    }
}