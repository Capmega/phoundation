<?php

/**
 * Trait TraitDataEntryDatabase
 *
 * This trait contains methods for DataEntry objects that require a database
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryDatabase
{
    /**
     * Returns the database for this object
     *
     * @return string|int|null
     */
    public function getDatabase(): string|int|null
    {
        return $this->getTypesafe('string|int', 'database');
    }


    /**
     * Sets the database for this object
     *
     * @param string|int|null $database
     *
     * @return static
     */
    public function setDatabase(string|int|null $database): static
    {
        return $this->set(get_null($database), 'database');
    }
}
