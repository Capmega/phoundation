<?php

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;

interface StashInterface
{
    /**
     * Unstashes the git changes
     *
     * @param array|string|null $path
     * @return static
     */
    public function stash(array|string|null $path = null): static;

    /**
     * Unstashes the git changes
     *
     * @return static
     */
    public function pop(): static;

    /**
     * Lists the available stashes in the git repository
     *
     * @return IteratorInterface
     */
    public function getList(): IteratorInterface;

    /**
     * Lists the changes available in the top most stash in the git repository
     *
     * @return array
     */
    public function getShow(): array;
}
