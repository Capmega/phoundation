<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Core\Session;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;

/**
 * SignIns class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class SignIns extends DataList
{
    /**
     * SignIns class constructor
     *
     * @param SignIn|null $parent
     * @param string|null $id_column
     */
    public function __construct(?SignIn $parent = null, ?string $id_column = null)
    {
        $this->entry_class = SignIn::class;
        self::$table       = SignIn::getTable();

        $this->setHtmlQuery('SELECT    `accounts_signins`.`id`,
                                             `accounts_signins`.`created_on`,
                                             `accounts_signins`.`ip_address`, 
                                             `accounts_signins`.`longitude`, 
                                             `accounts_signins`.`latitude`, 
                                             `geo_countries`.`name` AS `country`,  
                                             `geo_cities`.`name`    AS `city`  
                                   FROM      `accounts_signins` 
                                   LEFT JOIN `geo_countries`
                                   ON        `accounts_signins`.`countries_id` = `geo_countries`.`id` 
                                   LEFT JOIN `geo_cities`
                                   ON        `accounts_signins`.`cities_id`    = `geo_cities`.`id` 
                                   WHERE     `accounts_signins`.`created_by`   = :created_by 
                                   ORDER BY  `created_on`', [':created_by' => Session::getUser()->getId()]);

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
     * Returns an HTML <select> object with all available customers
     *
     * @param string $name
     * @return Select
     */
    public static function getHtmlSelect(string $name = 'customers_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT    `accounts_signins`.`id`,
                                                   `accounts_signins`.`created_on`,
                                         WHERE     `created_by` = :created_by 
                                         ORDER BY  `created_on`', [':created_by' => Session::getUser()->getId()])
            ->setName($name)
            ->setNone(tr('Please select a customer'))
            ->setEmpty(tr('No customers available'));
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
    public function save(): static
    {
        // TODO: Implement save() method.
    }

    protected function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // TODO: Implement loadDetails() method.
    }
}