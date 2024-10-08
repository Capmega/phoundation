<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Databases\Sql\Interfaces\SqlQueryInterface;
use Phoundation\Databases\Sql\SqlQuery;

/**
 * Trait TraitDataEntrySqlQuery
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntrySqlQuery
{
    /**
     * Returns the sql_query for this object
     *
     * @return SqlQueryInterface|null
     */
    public function getSqlQuery(): ?SqlQueryInterface
    {
        $sql_query = $this->get('string', 'sql_query');
        if ($sql_query) {
            return new SqlQuery($sql_query);
        }

        return null;
    }


    /**
     * Sets the sql_queryes_id for this object
     *
     * @param SqlQueryInterface|string|null $sql_query
     *
     * @return static
     */
    public function setSqlQuery(SqlQueryInterface|string|null $sql_query): static
    {
        return $this->set('sql_query', SqlQuery::new($sql_query)
                                                          ->getQuery());
    }
}
