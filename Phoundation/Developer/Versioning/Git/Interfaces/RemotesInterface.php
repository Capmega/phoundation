<?php

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;


interface RemotesInterface extends IteratorInterface
{
    /**
     * Loads the remotes for the specified git process
     *
     * @return static
     */
    public function load(): static;
}
