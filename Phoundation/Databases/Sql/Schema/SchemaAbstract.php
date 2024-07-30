<?php

/**
 * SchemaAbstract class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Databases\Sql\Schema\Interfaces\SchemaAbstractInterface;
use Phoundation\Databases\Sql\Schema\Interfaces\SchemaInterface;
use Phoundation\Databases\Sql\Sql;

abstract class SchemaAbstract implements SchemaAbstractInterface
{
    /**
     * The database interface for this schema
     *
     * @var Sql $sql
     */
    protected Sql $sql;

    /**
     * The SQL configuration
     *
     * @var array $configuration
     */
    protected array $configuration;

    /**
     * The parent object
     *
     * @var SchemaAbstractInterface|SchemaInterface $parent
     */
    protected SchemaAbstractInterface|SchemaInterface $parent;

    /**
     * The name for this object
     *
     * @var string $database
     */
    protected string $database;


    /**
     * SchemaAbstract class constructor
     *
     * @param string                                  $database
     * @param Sql                                     $sql
     * @param SchemaAbstractInterface|SchemaInterface $parent
     */
    public function __construct(string $database, Sql $sql, SchemaAbstractInterface|SchemaInterface $parent)
    {
        $this->sql                       = $sql;
        $this->database                  = $database;
        $this->parent                    = $parent;
        $this->configuration             = $sql->getConfiguration();
        $this->configuration['database'] = $database;
    }


    /**
     * Returns a new SchemaAbstract type object
     *
     * @param string                                  $name
     * @param Sql                                     $sql
     * @param SchemaAbstractInterface|SchemaInterface $parent
     *
     * @return static
     */
    public static function new(string $name, Sql $sql, SchemaAbstractInterface|SchemaInterface $parent): static
    {
        return new static($name, $sql, $parent);
    }


    /**
     * Returns the SQL object for this schema
     *
     * @return SqlInterface
     */
    public function getSqlObject(): SqlInterface
    {
        return $this->sql;
    }


    /**
     * Reload the table schema
     *
     * @return void
     */
    public function reload(): void
    {
        $this->load();
    }
}
