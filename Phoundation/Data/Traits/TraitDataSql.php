<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Databases\Sql\Interfaces\SqlInterface;


/**
 * Trait TraitDataSql
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataSql
{
    /**
     * The sql interface for this object
     *
     * @var SqlInterface|null $sql
     */
    protected ?SqlInterface $sql = null;


    /**
     * Returns the SQL interface
     *
     * @return SqlInterface|null
     */
    public function getQuery(): ?SqlInterface
    {
        return $this->sql;
    }


    /**
     * Sets the SQL interface
     *
     * @param SqlInterface|null $sql
     *
     * @return static
     */
    public function setQuery(SqlInterface|null $sql): static
    {
        $this->sql = $sql;
        return $this;
    }
}
