<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Schema\Interfaces;

use Phoundation\Databases\Sql\Schema\Database;
use Phoundation\Databases\Sql\Schema\Table;

interface DatabaseInterface extends SchemaAbstractInterface
{
    /**
     * Returns the database name
     *
     * @return string|null
     */

    public function getName(): ?string;

    /**
     * Sets the database name
     *
     * This will effectively rename the database. Since MySQL does not support renaming operations, this requires
     * dumping the entire database and importing it under the new name and dropping the original. Depending on your
     * database size, this may take a while!
     *
     * @return static
     */

    public function setName(string $name): static;

    /**
     * Create this database
     *
     * @param bool $use
     *
     * @return static
     */
    public function create(bool $use = true): static;

    /**
     * Returns if the database exists in the database or not
     *
     * @return bool
     */
    public function exists(): bool;

    /**
     * Drop this database
     *
     * @return static
     */
    public function drop(): static;

    /**
     * Access a new Table object for the currently selected database
     *
     * @param string $name
     *
     * @return Table
     */
    public function table(string $name): Table;


    /**
     * Load the table parameters from the database
     *
     * @param array|string|int|null $identifiers
     * @param bool                  $clear
     * @param bool                  $only_if_empty
     *
     * @return static
     */
    public function load(array|string|int|null $identifiers = null, bool $clear = true, bool $only_if_empty = false): static;


    /**
     * Renames this database
     *
     * @see https://www.atlassian.com/data/admin/how-to-rename-a-database-in-mysql
     *
     * @param string $database_name
     *
     * @return static
     */
    public function rename(string $database_name): static;

    /**
     * Will copy the current database to the new name
     *
     * @param string $database_name
     *
     * @return static
     *
     * @see https://www.atlassian.com/data/admin/how-to-rename-a-database-in-mysql
     */
    public function copy(string $database_name): static;
}
