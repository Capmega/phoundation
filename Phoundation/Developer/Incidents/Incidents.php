<?php

declare(strict_types=1);

namespace Phoundation\Developer\Incidents;

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\User;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Table;

/**
 * Incidents class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Incidents extends DataList
{
    /**
     * Users class constructor
     *
     * @param Role|User|null $parent
     * @param string|null $id_column
     */
    public function __construct(Role|User|null $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Incident::class;
        self::$table       = Incident::getTable();

        $this->setHtmlQuery('SELECT   `id`, `created_on`, `status`, `type`, `title` 
                                   FROM     `developer_incidents` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `created_on`');
        parent::__construct($parent, $id_column);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_signins';
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @return Table
     */
    public function getHtmlTable(): Table
    {
        $table = parent::getHtmlTable();
        $table->setCheckboxSelectors(true);

        return $table;
    }


    /**
     * @inheritDoc
     */
    protected function load(string|int|null $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }


    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }
}