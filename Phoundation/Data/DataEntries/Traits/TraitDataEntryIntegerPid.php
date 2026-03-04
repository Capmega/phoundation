<?php

/**
 * Trait TraitDataEntryIntegerPid
 *
 * This trait contains methods for DataEntry objects that require a pid
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


use Phoundation\Exception\OutOfBoundsException;


trait TraitDataEntryIntegerPid
{
    /**
     * Returns the pid for this object
     *
     * @return int|null
     */
    public function getPid(): int|null
    {
        return $this->getTypesafe('int', 'pid');
    }


    /**
     * Sets the pid for this object
     *
     * @param int|null $id
     *
     * @return static
     */
    public function setPid(int|null $id): static
    {
        if ($id <= 0) {
            throw new OutOfBoundsException(ts('The specified pid ":pid" is negative, which is not allowed as it must be a positive integer.', [
                ':pid' => $id
            ]));
        }

        return $this->set(get_null($id), 'pid');
    }
}
