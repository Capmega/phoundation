<?php

/**
 * Trait TraitDataDatabase
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataDatabase
{
    /**
     * The database for this object
     *
     * @var string|null $database
     */
    protected ?string $database = null;


    /**
     * Returns the database
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->database;
    }


    /**
     * Sets the database
     *
     * @param string|null $database
     *
     * @return static
     */
    public function setDatabase(?string $database): static
    {
        $this->database = get_null($database);
        return $this;
    }
}
