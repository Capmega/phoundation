<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

interface CliTableInterface
{
    /**
     * Displays a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'id'): static;
}
