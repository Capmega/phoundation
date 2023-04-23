<?php

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait DataEntryIpAddress
 *
 * This trait contains methods for DataEntry objects that require ip_address
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryIpAddress
{
    /**
     * Returns the ip address for this user
     *
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->getDataValue('ip_address');
    }


    /**
     * Sets the ip address for this user
     *
     * @param string|null $ip_address
     * @return static
     */
    public function setIpAddress(string|null $ip_address): static
    {
        $this->setDataValue('net_len', strlen($ip_address));
        $this->setDataValue('ip_address', $ip_address);
        return $this->setDataValue('ip_address_human', $ip_address);
    }
}