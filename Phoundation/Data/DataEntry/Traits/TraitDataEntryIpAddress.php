<?php

/**
 * Trait TraitDataEntryIpAddress
 *
 * This trait contains methods for DataEntry objects that require ip_address
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

trait TraitDataEntryIpAddress
{
    /**
     * Returns the ip address for this user
     *
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->getTypesafe('string', 'ip_address');
    }


    /**
     * Sets the ip address for this user
     *
     * @param string|null $ip_address
     *
     * @return static
     */
    public function setIpAddress(?string $ip_address): static
    {
        $this->set(strlen($ip_address), 'net_len');
        $this->set($ip_address, 'ip_address');

        return $this->set($ip_address, 'ip_address_human');
    }
}
