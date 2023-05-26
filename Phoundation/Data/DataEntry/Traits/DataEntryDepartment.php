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
     * @return int|null
     */
    public function getDepartmentsId(): ?int
    {
        return get_null((integer) $this->getDataValue('departments_id'));
    }


    /**
     * Sets the departments_id for this object
     *
     * @param string|int|null $departments_id
     * @return static
     */
    public function setDepartmentsId(string|int|null $departments_id): static
    {
        if ($departments_id and !is_natural($departments_id)) {
            throw new OutOfBoundsException(tr('Specified departments_id ":id" is not numeric', [
                ':id' => $departments_id
            ]));
        }

        return $this->setDataValue('departments_id', (integer) $departments_id);
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
     * @param Department|string|int|null $departments_id
     * @return static
     */
    public function setDepartment(Department|string|int|null $departments_id): static
    {
        if (!is_numeric($departments_id)) {
            $departments_id = Department::get($departments_id);
        }

        if (is_object($departments_id)) {
            $departments_id = $departments_id->getId();
        }

        return $this->setDataValue('departments_id', $departments_id);
    }
}