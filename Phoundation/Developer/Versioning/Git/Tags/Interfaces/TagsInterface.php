<?php

namespace Phoundation\Developer\Versioning\Git\Tags\Interfaces;

interface TagsInterface
{
    /**
     * Loads the remotes for the specified git process
     *
     * @return static
     */
    public function load(): static;
}
