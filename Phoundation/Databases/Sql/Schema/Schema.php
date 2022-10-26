<?php

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Schema class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Schema
{
    /**
     * The instance configuration for this schema
     *
     * @var string|null $instance
     */
    protected ?string $instance_name = null;

    /**
     * The database for this schema
     *
     * @var Sql $sql
     */
    protected Sql $sql;



    /**
     * Schema constructor
     */
    public function __construct(?string $instance_name = null)
    {
        // Check if the specified database exists
        if (!$instance_name) {
            throw new OutOfBoundsException(tr('No instance name specified'));
        }

        $this->instance_name = $instance_name;
        $this->sql = new Sql($instance_name);
    }



    /**
     * Access a new table object
     *
     * @param String|null $name
     * @return Table
     */
    public function table(?String $name): Table
    {
        return new Table($name);
    }
}