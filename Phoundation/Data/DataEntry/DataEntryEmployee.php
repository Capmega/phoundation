<?php

namespace Phoundation\Data\DataEntry;

use Phoundation\Business\Companies\Employees\Employee;



/**
 * Trait DataEntryEmployee
 *
 * This trait contains methods for DataEntry objects that require an employee
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryEmployee
{
    /**
     * The branch for this object
     *
     * @var Employee|null $employee
     */
    protected ?Employee $employee;



    /**
     * Returns the employees_id for this object
     *
     * @return string|null
     */
    public function getEmployeesId(): ?string
    {
        return $this->getDataValue('employees_id');
    }



    /**
     * Sets the employees_id for this object
     *
     * @param string|null $employees_id
     * @return static
     */
    public function setEmployeesId(?string $employees_id): static
    {
        return $this->setDataValue('employees_id', $employees_id);
    }



    /**
     * Returns the employees_id for this object
     *
     * @return Employee|null
     */
    public function getEmployee(): ?Employee
    {
        $employees_id = $this->getDataValue('employees_id');

        if ($employees_id) {
            return new Employee($employees_id);
        }

        return null;
    }



    /**
     * Sets the employees_id for this object
     *
     * @param Employee|null $employee
     * @return static
     */
    public function setEmployee(?Employee $employee): static
    {
        if (is_object($employee)) {
            $employee = $employee->getId();
        }

        return $this->setDataValue('employees_id', $employee);
    }
}