<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies\Departments;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;


/**
 *  Class Department
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Department extends DataEntry
{
    use DataEntryNameDescription;

    /**
     * Department class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'company department';
        self::$table        = Department::getTable();

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'business_departments';
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DataEntryFieldDefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DataEntryFieldDefinitionsInterface $field_definitions): void
    {
        $field_definitions;
    }
}