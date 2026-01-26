<?php

/**
 * Schema class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Databases\Sql\Schema\Interfaces\DatabaseInterface;
use Phoundation\Databases\Sql\Schema\Interfaces\SchemaInterface;
use Phoundation\Databases\Sql\Schema\Interfaces\TableInterface;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;


class Schema implements SchemaInterface
{
    use TraitDataConnector;


    /**
     * The instance configuration for this schema
     *
     * @var string|null $instance
     */
    protected ?string $connector = null;

    /**
     * The databases for this schema
     *
     * @var array $databases
     */
    protected array $databases = [];

    /**
     * The current database used
     *
     * @var string|null $current_database
     */
    protected ?string $current_database = null;

    /**
     * The SQL engine
     *
     * @var SqlInterface $sql
     */
    protected SqlInterface $sql;


    /**
     * Schema constructor
     */
    public function __construct(?string $connector = null, bool $use_database = false)
    {
        // Check if the specified database exists
        if (!$connector) {
            throw new OutOfBoundsException(tr('No connector specified'));
        }

        $this->setConnector($connector);
        $this->sql = new Sql($this->o_connector, $use_database);
    }


    /**
     * Access a new Table object for the currently selected database
     *
     * @param string      $name
     * @param string|null $database_name
     *
     * @return TableInterface
     */
    public function getTableObject(string $name, ?string $database_name = null): TableInterface
    {
        if (!$name) {
            throw new OutOfBoundsException(tr('No table specified'));
        }

        return $this->getDatabaseObject($database_name)
                    ->table($name);
    }


    /**
     * Access a new Database object
     *
     * @param string|null $database
     * @param bool        $use
     *
     * @return DatabaseInterface
     */
    public function getDatabaseObject(?string $database = null, bool $use = true): DatabaseInterface
    {
        if (!$database) {
            // Default to system database
            $database = config()->getString('databases.connectors.system.database');
        }

        // If we do not have this database object  yet, create it now
        if (!array_key_exists($database, $this->databases)) {
            $this->databases[$database] = new Database($database, $this->sql, $this);
        }

        // Set current database and return a database object
        if ($use) {
            $this->databases[$database]->getSqlObject()->use($database);

        } else {
            $this->databases[$database]->getSqlObject()->setDatabase($database);
        }

        $this->current_database = $database;
        return $this->databases[$database];
    }


    /**
     * Returns the current database
     *
     * @return string|null
     */
    public function getCurrent(): ?string
    {
        return $this->current_database;
    }
}
