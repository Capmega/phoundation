<?php

namespace Phoundation\Servers;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * SshAccounts class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class SshAccounts extends DataList
{
    /**
     * SshAccounts class constructor
     *
     * @param DataEntry|null $parent
     * @param string|null $id_column
     */
    public function __construct(?DataEntry $parent = null, ?string $id_column = null)
    {
        $this->entry_class = SshAccount::class;
        $this->table_name  = 'ssh_accounts';

        $this->setHtmlQuery('SELECT   `id`, `name`, `code`, `hostname`, `port`, `created_on` 
                                   FROM     `ssh_accounts` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct($parent, $id_column);
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
     * Returns an HTML <select> object with all available ssh_accounts_id
     *
     * @param string $name
     * @return Select
     */
    public static function getHtmlSelect(string $name = 'ssh_accounts_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT    `id`, `name` 
                                          FROM     `ssh_accounts`
                                          WHERE    `status` IS NULL 
                                          ORDER BY `name`')
            ->setName($name)
            ->setNone(tr('Please select an SSH account'))
            ->setEmpty(tr('No SSH accounts available'));
    }



    /**
     * @inheritDoc
     */
    protected function load(bool|string|null $id_column = false): static
    {
        // TODO: Implement load() method.
    }



    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }

    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }
}