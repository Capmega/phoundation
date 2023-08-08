<?php

namespace Phoundation\Business\Companies\Interfaces;


use Phoundation\Business\Companies\Branches\Branches;
use Phoundation\Business\Companies\Branches\Interfaces\BranchesInterface;
use Phoundation\Business\Companies\Departments\Departments;
use Phoundation\Business\Companies\Departments\Interfaces\DepartmentsInterface;

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
interface CompanyInterface
{
    /**
     * Access company branches
     *
     * @return BranchesInterface
     */
    public function getBranches(): BranchesInterface;

    /**
     * Access company branches
     *
     * @return DepartmentsInterface
     */
    public function getDepartments(): DepartmentsInterface;

    /**
     * @inheritDoc
     */
    public function save(bool $force = false, ?string $comments = null): static;
}