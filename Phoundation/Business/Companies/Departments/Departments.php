<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies\Departments;

use Phoundation\Business\Companies\Departments\Interfaces\DepartmentsInterface;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;


/**
 *  Class Departments
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Departments extends DataList implements DepartmentsInterface
{
    /**
     * Departments class constructor
     */
    public function __construct()
    {
        $this->entry_class = Department::class;
        $this->table       = 'business_departments';

        $this->setQuery('SELECT   `id`, `name`, `email`, `status`, `created_on` 
                               FROM     `business_departments` 
                               WHERE    `status` IS NULL 
                               ORDER BY `name`');
        parent::__construct();
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
            ->setName('departments_id')
            ->setNone(tr('Select a department'))
            ->setEmpty(tr('No departments available'));
    }
}