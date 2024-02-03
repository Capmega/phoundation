<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryIpAddress
 *
 * This trait contains methods for DataEntry objects that require ip_address
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        return $this->getSourceColumnValue('string', 'ip_address');
    }


    /**
     * Sets the ip address for this user
     *
     * @param string|null $ip_address
     * @return static
     */
    public function setIpAddress(?string $ip_address): static
    {
        $this->setSourceValue('net_len', strlen($ip_address));
        $this->setSourceValue('ip_address', $ip_address);
        return $this->setSourceValue('ip_address_human', $ip_address);
    }
}
