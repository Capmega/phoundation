<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;

interface BranchesInterface extends IteratorInterface
{
    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param PhoDirectoryInterface $directory
     *
     * @return static
     */
    public function setDirectory(PhoDirectoryInterface $directory): static;


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'branch'): static;
}
