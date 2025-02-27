<?php

/**
 * Trait TraitDataEntryArrayServers
 *
 * This trait contains methods for DataEntry objects that requires a list of servers
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryArrayServers
{
    /**
     * Returns the servers for this object
     *
     * @return array|null
     */
    public function getServers(): ?array
    {
        return $this->getTypesafe('array', 'servers');
    }


    /**
     * Sets the servers for this object
     *
     * @param array|string|int $servers
     *
     * @return static
     */
    public function setServers(array|string|int $servers): static
    {
        if ($servers) {
            if (is_string($servers)) {
                $servers = explode(',', $servers);
            }
        }

        return $this->set(get_null($servers), 'servers');
    }
}
