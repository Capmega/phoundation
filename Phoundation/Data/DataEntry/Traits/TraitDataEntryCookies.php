<?php

/**
 * Trait TraitDataEntryCookies
 *
 * This trait contains methods for DataEntry objects that require a name and cookies
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryCookies
{
    /**
     * Returns the cookies for this object
     *
     * @return array|null
     */
    public function getCookies(): ?array
    {
        return $this->getTypesafe('array', 'cookies');
    }


    /**
     * Sets the cookies for this object
     *
     * @param array|null $cookies
     *
     * @return static
     */
    public function setCookies(?array $cookies): static
    {
        return $this->set($cookies, 'cookies');
    }
}
