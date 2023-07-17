<?php

declare(strict_types=1);

namespace Phoundation\Geo\States;

use PDOStatement;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Geo\Countries\Country;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * States class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class States extends DataList
{
    /**
     * States class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, `name`, `status`, `created_on` 
                               FROM     `geo_states` 
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
        return 'geo_states';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return State::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return null;
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
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id'): SelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column)
            ->setName('states_id')
            ->setNone(tr('Select a state'))
            ->setEmpty(tr('No states available'));
    }
}