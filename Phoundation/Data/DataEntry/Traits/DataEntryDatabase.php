<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryDatabase
 *
 * This trait contains methods for DataEntry objects that require a database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryDatabase
{
    /**
     * Returns the database for this object
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'database');
    }


    /**
     * Sets the database for this object
     *
     * @param string|null $database
     * @return static
     */
    public function setDatabase(?string $database): static
    {
        return $this->setSourceValue('database', $database);
    }
}
