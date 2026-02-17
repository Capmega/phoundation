<?php

/**
 * Trait TraitDataEntryEmployee
 *
 * This trait contains methods for DataEntry objects that require an employee
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Business\Companies\Employees\Employee;
use Phoundation\Business\Companies\Employees\Interfaces\EmployeeInterface;


trait TraitDataEntryEmployee
{
    /**
     * Setup virtual configuration for Employees
     *
     * @return static
     */
    protected function addVirtualConfigurationEmployees(): static
    {
        return $this->addVirtualConfiguration('employees', Employee::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the employees_id column
     *
     * @return int|null
     */
    public function getEmployeesId(): ?int
    {
        return $this->getVirtualData('employees', 'int', 'id');
    }


    /**
     * Sets the employees_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setEmployeesId(?int $id): static
    {
        return $this->setVirtualData('employees', $id, 'id');
    }


    /**
     * Returns the employees_code column
     *
     * @return string|null
     */
    public function getEmployeesCode(): ?string
    {
        return $this->getVirtualData('employees', 'string', 'code');
    }


    /**
     * Sets the employees_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setEmployeesCode(?string $code): static
    {
        return $this->setVirtualData('employees', $code, 'code');
    }


    /**
     * Returns the employees_name column
     *
     * @return string|null
     */
    public function getEmployeesName(): ?string
    {
        return $this->getVirtualData('employees', 'string', 'name');
    }


    /**
     * Sets the employees_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setEmployeesName(?string $name): static
    {
        return $this->setVirtualData('employees', $name, 'name');
    }


    /**
     * Returns the Employee Object
     *
     * @return EmployeeInterface|null
     */
    public function getEmployeeObject(): ?EmployeeInterface
    {
        return $this->getVirtualObject('employees');
    }


    /**
     * Returns the employees_id for this user
     *
     * @param EmployeeInterface|null $_object
     *
     * @return static
     */
    public function setEmployeeObject(?EmployeeInterface $_object): static
    {
        return $this->setVirtualObject('employees', $_object);
    }
}
