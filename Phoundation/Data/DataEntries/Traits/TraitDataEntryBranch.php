<?php

/**
 * Trait TraitDataEntryBranch
 *
 * This trait contains methods for DataEntry objects that require a branch and description
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Business\Companies\Branches\Branch;
use Phoundation\Business\Companies\Branches\Interfaces\BranchInterface;


trait TraitDataEntryBranch
{
    /**
     * Setup virtual configuration for Branches
     *
     * @return static
     */
    protected function addVirtualConfigurationBranches(): static
    {
        return $this->addVirtualConfiguration('branches', Branch::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the branches_id column
     *
     * @return int|null
     */
    public function getBranchesId(): ?int
    {
        return $this->getVirtualData('branches', 'int', 'id');
    }


    /**
     * Sets the branches_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setBranchesId(?int $id): static
    {
        return $this->setVirtualData('branches', $id, 'id');
    }


    /**
     * Returns the branches_code column
     *
     * @return string|null
     */
    public function getBranchesCode(): ?string
    {
        return $this->getVirtualData('branches', 'string', 'code');
    }


    /**
     * Sets the branches_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setBranchesCode(?string $code): static
    {
        return $this->setVirtualData('branches', $code, 'code');
    }


    /**
     * Returns the branches_name column
     *
     * @return string|null
     */
    public function getBranchesName(): ?string
    {
        return $this->getVirtualData('branches', 'string', 'name');
    }


    /**
     * Sets the branches_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setBranchesName(?string $name): static
    {
        return $this->setVirtualData('branches', $name, 'name');
    }


    /**
     * Returns the Branch Object
     *
     * @return BranchInterface|null
     */
    public function getBranchObject(): ?BranchInterface
    {
        return $this->getVirtualObject('branches');
    }


    /**
     * Returns the branches_id for this user
     *
     * @param BranchInterface|null $_object
     *
     * @return static
     */
    public function setBranchObject(?BranchInterface $_object): static
    {
        return $this->setVirtualObject('branches', $_object);
    }
}
