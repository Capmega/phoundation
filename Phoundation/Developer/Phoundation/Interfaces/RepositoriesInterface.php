<?php
namespace Phoundation\Developer\Phoundation\Interfaces;

use Stringable;

interface RepositoriesInterface {
    /**
     * Adds the specified repository to this repositories list
     *
     * @param mixed                            $repository
     * @param float|Stringable|int|string|null $name
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return $this
     */
    public function add(mixed $repository, float|Stringable|int|string|null $name = null, bool $skip_null_values = true, bool $exception = true): static;

    /**
     * Scans for available phoundation and or phoundation plugin and or phoundation template repositories
     *
     * @return $this
     */
    public function scan(): static;
}