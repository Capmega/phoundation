<?php

namespace Phoundation\Business\Companies;

use Phoundation\Business\Companies\Branches\Branches;
use Phoundation\Business\Companies\Departments\Departments;
use Phoundation\Data\DataEntry;
use Phoundation\Data\DataEntryNameDescription;
use Phoundation\Data\DataList;



/**
 *  Class Company
 *
 *
 *
 * @see \Phoundation\Data\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Company extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Company class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        self::$entry_name = 'company';
        $this->table      = 'business_companies';

        parent::__construct($identifier);
    }



    /**
     * The branches for this company
     *
     * @var DataList $branches
     */
    protected DataList $branches;

    /**
     * The departments for this company
     *
     * @var DataList $departments
     */
    protected DataList $departments;



    /**
     * Access company branches
     *
     * @return Branches
     */
    public function branches(): Branches
    {
        if (!isset($this->branches)) {
            $this->branches = Branches::new($this);
        }

        return $this->branches;

    }



    /**
     * Access company branches
     *
     * @return Departments
     */
    public function departments(): Departments
    {
        if (!isset($this->departments)) {
            $this->departments = Departments::new($this);
        }

        return $this->departments;
    }



    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }



    /**
     * @inheritDoc
     */
    protected function setColumns(): void
    {
        // TODO: Implement setKeys() method.
    }



    /**
     * @inheritDoc
     */
    protected function load(int|string $identifier): void
    {
        // TODO: Implement load() method.
    }
}