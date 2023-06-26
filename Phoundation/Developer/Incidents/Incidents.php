<?php

declare(strict_types=1);

namespace Phoundation\Developer\Incidents;

use PDOStatement;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\User;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * Incidents class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Incidents extends DataList
{
    /**
     * Users class constructor
     */
    public function __construct()
    {
        $this->entry_class = Incident::class;
        $this->table       = 'developer_incidents';

        $this->setQuery('SELECT   `id`, `created_on`, `status`, `type`, `title` 
                                   FROM     `developer_incidents` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `created_on`');
        parent::__construct();
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
    public function load(?string $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     * @inheritDoc
     */
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
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


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'title', string $key_column = 'id'): SelectInterface
    {
        return InputSelect::new()
            ->setSourceQuery('SELECT   `' . $key_column . '`, `' . $value_column . '` 
                                         FROM     `' . $this->table . '` 
                                         WHERE    `status` IS NULL 
                                         ORDER BY `title` ASC')
            ->setName('incidents_id')
            ->setNone(tr('Select an incident'))
            ->setEmpty(tr('No incidents available'));
    }
}