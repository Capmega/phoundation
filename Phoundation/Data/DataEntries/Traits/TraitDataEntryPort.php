<?php

/**
 * Trait TraitDataEntryPort
 *
 * This trait contains methods for DataEntry objects that requires a IP port
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Exception\OutOfBoundsException;

trait TraitDataEntryPort
{
    /**
     * Returns the port for this object
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->getTypesafe('int', 'port');
    }


    /**
     * Sets the port for this object
     *
     * @param int|null $port
     *
     * @return static
     */
    public function setPort(?int $port): static
    {
        if ($port) {
            if (($port < 1) or ($port > 65535)) {
                throw new OutOfBoundsException(tr('Invalid port ":port" specified, it must be an integer value between 1 and 65535', [
                    ':port' => $port,
                ]));
            }
        }

        return $this->set(get_null($port), 'port');
    }
}
