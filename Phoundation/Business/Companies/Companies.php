<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;

/**
 * Class Companies
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataList
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Business
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
    public static function getTable(): ?string
    {
        return 'business_companies';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string|null
     */
    public static function getEntryClass(): ?string
    {
        return Company::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'seo_name';
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     *
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
     * @param string      $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null  $joins
     * @param array|null  $filters
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order, $joins, $filters)
                     ->setName('companies_id')
                     ->setNotSelectedLabel(tr('Select a company'))
                     ->setComponentEmptyLabel(tr('No companies available'));
    }
}
