<?php

/**
 * Trait TraitDataEntryIntegerIncidentsId
 *
 * This trait contains methods for DataEntry objects that require an incidents_id
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


use Phoundation\Exception\OutOfBoundsException;

trait TraitDataEntryIntegerIncidentsId
{
    /**
     * Returns the incidents_id for this object
     *
     * @return int|null
     */
    public function getIncidentsId(): int|null
    {
        return $this->getTypesafe('int', 'incidents_id');
    }


    /**
     * Sets the incidents_id for this object
     *
     * @param int|null $id
     *
     * @return static
     */
    public function setIncidentsId(int|null $id): static
    {
        if ($id <= 0) {
            throw new OutOfBoundsException(ts('The specified incidents_id ":incidents_id" is negative, which is not allowed as it must be a database id', [
                ':incidents_id' => $id
            ]));
        }

        return $this->set(get_null($id), 'incidents_id');
    }
}
