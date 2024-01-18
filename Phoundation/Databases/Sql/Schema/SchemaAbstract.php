<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Databases\Sql\Sql;


/**
 * SchemaAbstract class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
abstract class SchemaAbstract
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
     * @var SchemaAbstract|Schema $parent
     */
    protected SchemaAbstract|Schema $parent;

    /**
     * The name for this object
     *
     * @var string $name
     */
    protected string $name;


    /**
     * SchemaAbstract class constructor
     *
     * @param string $name
     * @param Sql $sql
     * @param SchemaAbstract|Schema $parent
     */
    public function __construct(string $name, Sql $sql, SchemaAbstract|Schema $parent)
    {
        $this->sql           = $sql;
        $this->name          = $name;
        $this->parent        = $parent;
        $this->configuration = $sql->getConfiguration();
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