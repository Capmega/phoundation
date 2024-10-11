<?php

declare(strict_types=1);

namespace Phoundation\Databases\Interfaces;

interface DatastoreInterface
{
    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static;
}
