<?php

/**
 * Trait TraitDataEntryPhones
 *
 * This trait contains methods for DataEntry objects that require phone numbers
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Utils\Strings;

trait TraitDataEntryPhones
{
    /**
     * Returns the phones for this object
     *
     * @return string|null
     */
    public function getPhones(): ?string
    {
        return $this->getValueTypesafe('string', 'phones');
    }


    /**
     * Sets the phones for this object
     *
     * @param array|string|null $phones
     *
     * @return static
     */
    public function setPhones(array|string|null $phones): static
    {
        return $this->set(Strings::force($phones), 'phones');
    }
}
