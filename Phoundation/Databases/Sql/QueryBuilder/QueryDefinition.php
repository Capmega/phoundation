<?php

/**
 * class QueryDefinition
 *
 * This class contains the definitions for a colum to select, filter, group, order or limit by
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql\QueryBuilder;

class QueryDefinition extends QueryObject
{
    /**
     * The query builder object that will generate the final query
     *
     * @var QueryBuilderInterface
     */
    protected QueryBuilderInterface $builder;

    /**
     * The name of the column
     *
     * @var string $column_name
     */
    protected string $column_name;


    /**
     * QueryDefinition class constructor
     *
     * @param string                $column_name
     * @param QueryBuilderInterface $builder
     */
    public function __construct(string $column_name, QueryBuilderInterface $builder)
    {
        $this->builder     = $builder;
        $this->column_name = $column_name;
    }


    public function getColumnName(): string
    {
        return $this->column_name;
    }
}
