<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Developer\Versioning\Git\RemoteRepository;
use Stringable;

interface RemoteRepositoriesInterface
{
    /**
     * Display the repositories on the CLI
     *
     * @return void
     */
    public function displayCliTable(): void;


    /**
     * Returns the specified repository
     *
     * @param Stringable|string|float|int
     * @param bool $exception
     *
     * @return RemoteRepository|null
     */
    public function get(Stringable|string|float|int $key, bool $exception = true): mixed;
}
