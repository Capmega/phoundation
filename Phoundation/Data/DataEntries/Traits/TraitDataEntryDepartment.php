<?php

/**
 * Trait TraitDataEntryDepartment
 *
 * This trait contains methods for DataEntry objects that require a department
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Business\Companies\Departments\Department;
use Phoundation\Business\Companies\Departments\Interfaces\DepartmentInterface;


trait TraitDataEntryDepartment
{
    /**
     * Setup virtual configuration for Departments
     *
     * @return static
     */
    protected function addVirtualConfigurationDepartments(): static
    {
        return $this->addVirtualConfiguration('departments', Department::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the departments_id column
     *
     * @return int|null
     */
    public function getDepartmentsId(): ?int
    {
        return $this->getVirtualData('departments', 'int', 'id');
    }


    /**
     * Sets the departments_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setDepartmentsId(?int $id): static
    {
        return $this->setVirtualData('departments', $id, 'id');
    }


    /**
     * Returns the departments_code column
     *
     * @return string|null
     */
    public function getDepartmentsCode(): ?string
    {
        return $this->getVirtualData('departments', 'string', 'code');
    }


    /**
     * Sets the departments_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setDepartmentsCode(?string $code): static
    {
        return $this->setVirtualData('departments', $code, 'code');
    }


    /**
     * Returns the departments_name column
     *
     * @return string|null
     */
    public function getDepartmentsName(): ?string
    {
        return $this->getVirtualData('departments', 'string', 'name');
    }


    /**
     * Sets the departments_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setDepartmentsName(?string $name): static
    {
        return $this->setVirtualData('departments', $name, 'name');
    }


    /**
     * Returns the Department Object
     *
     * @return DepartmentInterface|null
     */
    public function getDepartmentObject(): ?DepartmentInterface
    {
        return $this->getVirtualObject('departments');
    }


    /**
     * Returns the departments_id for this user
     *
     * @param DepartmentInterface|null $_object
     *
     * @return static
     */
    public function setDepartmentObject(?DepartmentInterface $_object): static
    {
        return $this->setVirtualObject('departments', $_object);
    }
}
