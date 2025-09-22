<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Developer\Versioning\Git\RemoteRepository;
use ReturnTypeWillChange;
use Stringable;

interface RemoteRepositoriesInterface
{
    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'repository'): static;


    /**
     * Returns the specified repository
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return RemoteRepository|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): mixed;
}
