<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies;

use Phoundation\Business\Companies\Branches\Branches;
use Phoundation\Business\Companies\Departments\Departments;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;

/**
 *  Class Company
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'company';
        $this->table      = 'business_companies';

        parent::__construct($identifier);
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
     * @inheritDoc
     */
    protected function validate(GetValidator|PostValidator|ArgvValidator $validator, bool $no_arguments_left = false, bool $modify = true): array
    {
        // TODO: Implement validate() method.
    }


    /**
     * Sets the available data keys for this entry
     *
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
        // TODO: Implement getFieldDefinitions() method.
    }
}