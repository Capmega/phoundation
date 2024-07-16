<?php

declare(strict_types=1);

namespace Phoundation\Servers\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;

interface SshAccountInterface extends DataEntryInterface
{
    /**
     * Returns the ssh_key for this object
     *
     * @return string|null
     */
    public function getSshKey(): ?string;


    /**
     * Sets the ssh_key for this object
     *
     * @param string|null $ssh_key
     *
     * @return static
     */
    public function setSshKey(?string $ssh_key): static;
}
