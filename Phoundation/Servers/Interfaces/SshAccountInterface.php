<?php

declare(strict_types=1);

namespace Phoundation\Servers\Interfaces;


use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;

/**
 * SshAccount class
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Servers
 */
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
