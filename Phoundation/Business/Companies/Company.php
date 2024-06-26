<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies;

use Phoundation\Business\Companies\Branches\Branches;
use Phoundation\Business\Companies\Branches\Interfaces\BranchesInterface;
use Phoundation\Business\Companies\Departments\Departments;
use Phoundation\Business\Companies\Departments\Interfaces\DepartmentsInterface;
use Phoundation\Business\Companies\Interfaces\CompanyInterface;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameDescription;

/**
 *  Class Company
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Companies
 */
class Company extends DataEntry implements CompanyInterface
{
    use TraitDataEntryNameDescription;

    /**
     * The branches for this company
     *
     * @var BranchesInterface $branches
     */
    protected BranchesInterface $branches;

    /**
     * The departments for this company
     *
     * @var DepartmentsInterface $departments
     */
    protected DepartmentsInterface $departments;


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'business_companies';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Company');
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
     * Access company branches
     *
     * @return BranchesInterface
     */
    public function getBranches(): BranchesInterface
    {
        if (!isset($this->branches)) {
            $this->branches = Branches::new($this);
        }

        return $this->branches;

    }


    /**
     * Access company branches
     *
     * @return DepartmentsInterface
     */
    public function getDepartments(): DepartmentsInterface
    {
        if (!isset($this->departments)) {
            $this->departments = Departments::new($this);
        }

        return $this->departments;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions;
    }
}
