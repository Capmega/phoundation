<?php

/**
 * Trait TraitDataEntryStringGlobalId
 *
 * This trait contains methods for DataEntry objects that require a global_id
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Exception\OutOfBoundsException;


trait TraitDataEntryStringGlobalId
{
    /**
     * Returns the global_id for this object
     *
     * @return string|null
     */
    public function getGlobalId(): string|null
    {
        return $this->getTypesafe('string', 'global_id');
    }


    /**
     * Sets the global_id for this object
     *
     * @param string|null $global_id
     *
     * @return static
     */
    public function setGlobalId(string|null $global_id): static
    {
        if ($global_id) {
            if (strlen($global_id) !== 8) {
                throw new OutOfBoundsException(ts('The specified global_id ":code" is not exactly 8 characters', [
                    ':code' => $global_id
                ]));
            }
        }

        return $this->set(get_null($global_id), 'global_id');
    }
}
