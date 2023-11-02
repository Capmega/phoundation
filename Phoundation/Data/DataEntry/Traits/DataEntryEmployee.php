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
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryEmployee
{
    /**
     * Returns the employees_id for this object
     *
     * @return int|null
     */
    public function getEmployeesId(): ?int
    {
        return $this->getSourceValue('int', 'employees_id');
    }


    /**
     * Sets the employees_id for this object
     *
     * @param int|null $employees_id
     * @return static
     */
    public function setEmployeesId(?int $employees_id): static
    {
        return $this->setSourceValue('employees_id', $employees_id);
    }


    /**
     * Returns the employee for this object
     *
     * @return Employee|null
     */
    public function getEmployee(): ?Employee
    {
        $employees_id = $this->getSourceValue('int', 'employees_id');

        if ($employees_id) {
            return new Employee($employees_id);
        }

        return null;
    }


    /**
     * Returns the employees_name for this object
     *
     * @return string|null
     */
    public function getEmployeesName(): ?string
    {
        return $this->getSourceValue('string', 'employees_name');
    }


    /**
     * Sets the employees_name for this object
     *
     * @param string|null $employees_name
     * @return static
     */
    public function setEmployeesName(?string $employees_name): static
    {
        return $this->setSourceValue('employees_name', $employees_name);
    }
}
