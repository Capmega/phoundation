<?php

/**
 * Trait TraitDataEntryStringLocalId
 *
 * This trait contains methods for DataEntry objects that require a local_id
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Exception\OutOfBoundsException;


trait TraitDataEntryStringLocalId
{
    /**
     * Returns the local_id for this object
     *
     * @return string|null
     */
    public function getLocalId(): string|null
    {
        return $this->getTypesafe('string', 'local_id');
    }


    /**
     * Sets the local_id for this object
     *
     * @param string|null $local_id
     *
     * @return static
     */
    public function setLocalId(string|null $local_id): static
    {
        if ($local_id) {
            if (strlen($local_id) !== 8) {
                throw new OutOfBoundsException(ts('The specified local_id ":code" is not exactly 8 characters', [
                    ':code' => $local_id
                ]));
            }
        }

        return $this->set(get_null($local_id), 'local_id');
    }
}
