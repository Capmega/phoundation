<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies\Employees;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;


/**
 * Class Employees
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Employees extends DataList
{
    /**
     * Employees class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, `name`, `email`, `status`, `created_on` 
                                   FROM     `business_employees` 
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
        return 'business_employees';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Employee::class;
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
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @param array|null $joins
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id', ?string $order = null, ?array $joins = null): InputSelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order)
            ->setName('employees_id')
            ->setNone(tr('Select a employee'))
            ->setObjectEmpty(tr('No employees available'));
    }
}
