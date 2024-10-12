<?php

/**
 * Timezones class
 *
 *
 *
 * @see       DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Geo
 */


declare(strict_types=1);

namespace Phoundation\Geo\Timezones;

use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;


class Timezones extends DataIterator
{
    /**
     * Timezones class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, `name`, `status`, `created_on` 
                               FROM     `geo_timezones` 
                               WHERE    `status` IS NULL 
                               ORDER BY `name`');
        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'geo_timezones';
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return Timezone::class;
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
     * Returns an HTML <select> object with all states available in this timezone
     *
     * @param string $name
     *
     * @return InputSelect
     */
    public static function getHtmlTimezonesSelect(string $name = 'timezones_id'): InputSelect
    {
        return InputSelect::new()
                          ->setConnectorObject(static::getDefaultConnectorObject())
                          ->setSourceQuery('SELECT `id`, `name` 
                                            FROM  `geo_timezones` 
                                            WHERE `status` IS NULL ORDER BY `name`')
                          ->setName($name)
                          ->setNotSelectedLabel(tr('Select a timezone'))
                          ->setComponentEmptyLabel(tr('No timezones available'));
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     *
     * @return HtmlTableInterface
     */
    public function getHtmlTableObject(array|string|null $columns = null): HtmlTableInterface
    {
        $table = parent::getHtmlTableObject();
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
                     ->setName('timezones_id')
                     ->setNotSelectedLabel(tr('Select a timezone'))
                     ->setComponentEmptyLabel(tr('No timezones available'));
    }
}
