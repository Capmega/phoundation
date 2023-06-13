<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies;

use Phoundation\Business\Companies\Branches\Branches;
use Phoundation\Business\Companies\Departments\Departments;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\DataEntry\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;


/**
 *  Class Company
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Company extends DataEntry
{
    use DataEntryNameDescription;


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
     * Company class constructor
     *
     * @param DataEntry|string|int|null $identifier
     */
    public function __construct(DataEntry|string|int|null $identifier = null)
    {
        $this->entry_name   = 'company';

        parent::__construct($identifier);
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
    public function save(?string $comments = null): static
    {
        // TODO: Implement save() method.
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DefinitionsInterface $field_definitions): void
    {
        $field_definitions;
    }
}