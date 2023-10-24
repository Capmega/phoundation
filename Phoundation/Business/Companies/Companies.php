<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies;

use PDOStatement;
use Phoundation\Business\Companies\Branches\Branch;
use Phoundation\Business\Customers\Customer;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;
use Phoundation\Web\Http\Html\Components\Interfaces\HtmlTableInterface;
use Phoundation\Web\Http\Html\Components\HtmlTable;
use Phoundation\Web\Http\Html\Enums\TableIdColumn;


/**
 * Class Companies
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Companies extends DataList
{
    /**
     * Companies class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, `name`, `email`, `status`, `created_on` 
                               FROM     `business_companies` 
                               WHERE    `status` IS NULL 
                               ORDER BY `name`');

        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'business_companies';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Company::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'seo_name';
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
        $table->setTableIdColumn(TableIdColumn::checkbox);

        return $table;
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id', ?string $order = null): InputSelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order)
            ->setName('companies_id')
            ->setNone(tr('Select a company'))
            ->setObjectEmpty(tr('No companies available'));
    }
}
