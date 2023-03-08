<?php

namespace Phoundation\Business\Companies\Employees;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;


/**
 * Class Employee
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Employee extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Employee class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name = 'company employee';
        $this->table      = 'business_employees';

        parent::__construct($identifier);
    }



    /**
     * @inheritDoc
     */
    public function save(?string $comments = null): static
    {
        return $this;
    }



    /**
     * @inheritDoc
     */
    protected function load(int|string $identifier): void
    {
        // TODO: Implement load() method.
    }



    /**
     * @inheritDoc
     */
    public static function getFieldDefinitions(): array
    {
        // TODO: Implement getFieldDefinitions() method.
    }
}