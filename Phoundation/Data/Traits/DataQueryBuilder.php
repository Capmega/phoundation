<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;


/**
 * Trait DataQueryBuilder
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opentable.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataQueryBuilder
{
    /**
     * The data entry
     *
     * @var QueryBuilderInterface $query_builder
     */
    protected QueryBuilderInterface $query_builder;


    /**
     * Returns the query builder
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->query_builder;
    }


    /**
     * Sets the query builder
     *
     * @param QueryBuilderInterface $query_builder
     * @return static
     */
    public function setQueryBuilder(QueryBuilderInterface $query_builder): static
    {
        $this->query_builder = $query_builder;
        return $this;
    }
}