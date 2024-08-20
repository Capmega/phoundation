<?php

declare(strict_types=1);

namespace Phoundation\Developer\Phoundation\Repositories\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Stringable;

interface RepositoriesInterface extends IteratorInterface
{
    /**
     * Adds the specified repository to this repositories list
     *
     * @param mixed                            $repository
     * @param float|Stringable|int|string|null $name
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function add(mixed $repository, float|Stringable|int|string|null $name = null, bool $skip_null_values = true, bool $exception = true): static;

    /**
     * Scans for available phoundation and or phoundation plugin and or phoundation template repositories
     *
     * @return static
     */
    public function scan(): static;
}
