<?php

namespace Phoundation\Data\DataEntry;

use Phoundation\Business\Companies\Branches\Branch;



/**
 * Trait DataEntryBranch
 *
 * This trait contains methods for DataEntry objects that require a branch
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryBranch
{
    /**
     * The branch for this object
     *
     * @var Branch|null $branch
     */
    protected ?Branch $branch;



    /**
     * Returns the branches_id for this object
     *
     * @return string|null
     */
    public function getBranchesId(): ?string
    {
        return $this->getDataValue('branches_id');
    }



    /**
     * Sets the branches_id for this object
     *
     * @param string|null $branches_id
     * @return static
     */
    public function setBranchesId(?string $branches_id): static
    {
        return $this->setDataValue('branches_id', $branches_id);
    }



    /**
     * Returns the branches_id for this object
     *
     * @return Branch|null
     */
    public function getBranch(): ?Branch
    {
        $branches_id = $this->getDataValue('branches_id');

        if ($branches_id) {
            return new Branch($branches_id);
        }

        return null;
    }



    /**
     * Sets the branches_id for this object
     *
     * @param Branch|null $branch
     * @return static
     */
    public function setBranch(?Branch $branch): static
    {
        if (is_object($branch)) {
            $branch = $branch->getId();
        }

        return $this->setDataValue('branches_id', $branch);
    }
}