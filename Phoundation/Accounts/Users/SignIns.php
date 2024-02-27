<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;


/**
 * SignIns class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class SignIns extends DataList
{
    /**
     * SignIns class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT    `accounts_signins`.`id`,
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

        parent::__construct();
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
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return SignIn::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     * @return HtmlTableInterface
     */
    public function getHtmlTable(array|string|null $columns = null): HtmlTableInterface
    {
        $table = parent::getHtmlTable();
        $table->setCheckboxSelectors(EnumTableIdColumn::checkbox);

        return $table;
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null $joins
     * @param array|null $filters
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'created_on', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return InputSelect::new()
            ->setConnector(static::getDefaultConnectorName())
            ->setSourceQuery('SELECT    `accounts_signins`.`' . $key_column . '`,
                                                   `accounts_signins`.`' . $value_column . '`,
                                         WHERE     `created_by` = :created_by 
                                         ORDER BY  `created_on`', [':created_by' => Session::getUser()->getId()])
            ->setName('sign_ins_id')
            ->setNone(tr('Select a sign-in'))
            ->setObjectEmpty(tr('No sign-ins available'));
    }
}
