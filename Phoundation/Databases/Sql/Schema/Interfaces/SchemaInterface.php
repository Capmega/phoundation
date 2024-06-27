<?php

/**
 * Interface SchemaInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */

namespace Phoundation\Databases\Sql\Schema\Interfaces;

interface SchemaInterface
{
    /**
     * Access a new Table object for the currently selected database
     *
     * @param string      $name
     * @param string|null $database_name
     *
     * @return TableInterface
     */
    public function getTableObject(string $name, ?string $database_name = null): TableInterface;

    /**
     * Access a new Database object
     *
     * @param string|null $name
     *
     * @return DatabaseInterface
     */
    public function getDatabaseObject(?string $name = null): DatabaseInterface;

    /**
     * Returns the current database
     *
     * @return string|null
     */
    public function getCurrent(): ?string;
}