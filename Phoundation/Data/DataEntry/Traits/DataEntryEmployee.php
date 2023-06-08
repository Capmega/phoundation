<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Companies\Employees\Employee;
use Phoundation\Exception\OutOfBoundsException;

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
     * @return int|null
     */
    public function getEmployeesId(): ?int
    {
        return $this->getDataValue('int', 'employees_id');
    }


    /**
     * Sets the employees_id for this object
     *
     * @param string|int|null $employees_id
     * @return static
     */
    public function setEmployeesId(string|int|null $employees_id): static
    {
        if ($employees_id and !is_natural($employees_id)) {
            throw new OutOfBoundsException(tr('Specified employees_id ":id" is not numeric', [
                ':id' => $employees_id
            ]));
        }

        return $this->setDataValue('employees_id', get_null(isset_get_typed('integer', $employees_id)));
    }


    /**
     * Returns the employees_id for this object
     *
     * @return Employee|null
     */
    public function getEmployee(): ?Employee
    {
        $employees_id = $this->getDataValue('string', 'employees_id');

        if ($employees_id) {
            return new Employee($employees_id);
        }

        return null;
    }


    /**
     * Sets the employees_id for this object
     *
     * @param Employee|string|int|null $employee
     * @return static
     */
    public function setEmployee(Employee|string|int|null $employee): static
    {
        if ($employee) {
            if (!is_numeric($employee)) {
                $employee = Employee::get($employee);
            }

            if (is_object($employee)) {
                $employee = $employee->getId();
            }
        }

        return $this->setEmployeesId(get_null($employee));
    }
}