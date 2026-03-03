<?php

/**
 * Trait TraitDataEntryStringRemoteIp
 *
 * This trait contains methods for DataEntry objects that require remote_ip
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Exception\OutOfBoundsException;


trait TraitDataEntryStringRemoteIp
{
    /**
     * Returns the remote_ip for this object
     *
     * @return string|null
     */
    public function getRemoteIp(): string|null
    {
        return $this->getTypesafe('string', 'remote_ip');
    }


    /**
     * Sets the remote_ip for this object
     *
     * @param string|null $remote_ip
     *
     * @return static
     */
    public function setRemoteIp(string|null $remote_ip): static
    {
        if ($remote_ip) {
            if (strlen($remote_ip) > 48) {
                throw new OutOfBoundsException(ts('Specified remote_ip ":remote_ip" has more than 48 characters', [
                    ':remote_ip' => $remote_ip
                ]));
            }
        }

        return $this->set(get_null($remote_ip), 'remote_ip');
    }
}
