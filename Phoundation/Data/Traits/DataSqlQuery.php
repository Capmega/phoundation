<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Databases\Sql\Interfaces\SqlQueryInterface;
use Phoundation\Databases\Sql\SqlQuery;


/**
 * Trait DataSqlQuery
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataSqlQuery
{
    /**
     * The query for this object
     *
     * @var SqlQueryInterface|null $query
     */
    protected ?SqlQueryInterface $query = null;


    /**
     * Returns the SQL query
     *
     * @return SqlQueryInterface|null
     */
    public function getQuery(): ?SqlQueryInterface
    {
        return $this->query;
    }


    /**
     * Sets the SQL query
     *
     * @param SqlQueryInterface|string|null $query
     * @return static
     */
    public function setQuery(SqlQueryInterface|string|null $query): static
    {
        $this->query = $query ? new SqlQuery($query) : null;
        return $this;
    }
}
