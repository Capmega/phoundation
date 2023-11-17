<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataDatabaseConnector
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opendebug.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataDatabaseConnector
{
    /**
     * Tracks the database connector where this DataEntry object is stored
     *
     * @var string $database_connector
     */
    protected string $database_connector = 'system';


    /**
     * Returns the name of the database connector where this DataEntry is stored
     *
     * @return string
     */
    public function getDatabaseConnector(): string
    {
        return $this->database_connector;
    }


    /**
     * Returns the name of the database connector where this DataEntry is stored
     *
     * @param string $database_connector
     * return static
     */
    public function setDatabaseConnector(string $database_connector): static
    {
        $this->database_connector = $database_connector;
        return $this;
    }
}