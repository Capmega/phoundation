<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Companies\Branches\Branch;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait DataEntryBranch
 *
 * This trait contains methods for DataEntry objects that require a branch
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryBranch
{
    /**
     * Returns the branches_id for this object
     *
     * @return int|null
     */
    public function getBranchesId(): ?int
    {
        return $this->getSourceValue('int', 'branches_id');
    }


    /**
     * Sets the branches_id for this object
     *
     * @param int|null $branches_id
     * @return static
     */
    public function setBranchesId(?int $branches_id): static
    {
        return $this->setSourceValue('branches_id', $branches_id);
    }


    /**
     * Returns the branch for this object
     *
     * @return Branch|null
     */
    public function getBranch(): ?Branch
    {
        $branches_id = $this->getSourceValue('int', 'branches_id');

        if ($branches_id) {
            return new Branch($branches_id);
        }

        return null;
    }


    /**
     * Returns the branches_name for this object
     *
     * @return string|null
     */
    public function getBranchesName(): ?string
    {
        return $this->getSourceValue('string', 'branches_name') ?? Branch::new($this->getBranchesId(), 'id')?->getName();
    }


    /**
     * Sets the branches_id for this object
     *
     * @param string|null $branches_name
     * @return static
     */
    public function setBranchesName(?string $branches_name): static
    {
        return $this->setSourceValue('branches_name', $branches_name);
    }
}
