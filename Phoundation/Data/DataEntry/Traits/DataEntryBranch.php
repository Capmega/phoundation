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
     * @return int|null
     */
    public function getBranchesId(): ?int
    {
        return $this->getDataValue('string', 'branches_id');
    }


    /**
     * Sets the branches_id for this object
     *
     * @param string|int|null $branches_id
     * @return static
     */
    public function setBranchesId(string|int|null $branches_id): static
    {
        if ($branches_id and !is_natural($branches_id)) {
            throw new OutOfBoundsException(tr('Specified branches_id ":id" is not numeric', [
                ':id' => $branches_id
            ]));
        }

        return $this->setDataValue('branches_id', get_null(isset_get_typed('integer', $branches_id)));
    }


    /**
     * Returns the branches_id for this object
     *
     * @return Branch|null
     */
    public function getBranch(): ?Branch
    {
        $branches_id = $this->getDataValue('string', 'branches_id');

        if ($branches_id) {
            return new Branch($branches_id);
        }

        return null;
    }


    /**
     * Sets the branches_id for this object
     *
     * @param Branch|string|int|null $branch
     * @return static
     */
    public function setBranch(Branch|string|int|null $branch): static
    {
        if ($branch) {
            if (!is_numeric($branch)) {
                $branch = Branch::get($branch);
            }

            if (is_object($branch)) {
                $branch = $branch->getId();
            }
        }

        return $this->setBranchesId(get_null($branch));
    }
}