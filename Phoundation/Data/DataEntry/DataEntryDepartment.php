<?php

namespace Phoundation\Data\DataEntry;

use Phoundation\Business\Companies\Departments\Department;



/**
 * Trait DataEntryDepartment
 *
 * This trait contains methods for DataEntry objects that require a department
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryDepartment
{
    /**
     * The department for this object
     *
     * @var Department|null $department
     */
    protected ?Department $department;



    /**
     * Returns the departments_id for this object
     *
     * @return string|null
     */
    public function getDepartmentsId(): ?string
    {
        return $this->getDataValue('departments_id');
    }



    /**
     * Sets the departments_id for this object
     *
     * @param string|null $departments_id
     * @return static
     */
    public function setDepartmentsId(?string $departments_id): static
    {
        return $this->setDataValue('departments_id', $departments_id);
    }



    /**
     * Returns the departments_id for this object
     *
     * @return Department|null
     */
    public function getDepartment(): ?Department
    {
        $departments_id = $this->getDataValue('departments_id');

        if ($departments_id) {
            return new Department($departments_id);
        }

        return null;
    }



    /**
     * Sets the departments_id for this object
     *
     * @param Department|null $department
     * @return static
     */
    public function setDepartment(?Department $department): static
    {
        if (is_object($department)) {
            $department = $department->getId();
        }

        return $this->setDataValue('departments_id', $department);
    }
}