<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Companies\Departments\Department;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait DataEntryDepartment
 *
 * This trait contains methods for DataEntry objects that require a department
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryDepartment
{
    /**
     * Returns the departments_id for this object
     *
     * @return int|null
     */
    public function getDepartmentsId(): ?int
    {
        return $this->getDataValue('int', 'departments_id');
    }


    /**
     * Sets the departments_id for this object
     *
     * @param int|null $departments_id
     * @return static
     */
    public function setDepartmentsId(?int $departments_id): static
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
     * Returns the departments_id for this object
     *
     * @return string|null
     */
    public function getDepartmentsName(): ?string
    {
        return $this->getDataValue('string', 'departments_name');
    }


    /**
     * Sets the departments_id for this object
     *
     * @param string|null $departments_name
     * @return static
     */
    public function setDepartmentsName(?string $departments_name): static
    {
        return $this->setDataValue('departments_name', $departments_name);
    }
}
