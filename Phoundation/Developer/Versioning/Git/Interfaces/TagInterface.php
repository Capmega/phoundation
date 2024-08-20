<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;

interface TagInterface
{
    /**
     * Creates a new tag
     *
     * @param string      $message
     * @param string      $annotation
     * @param string|null $commit
     * @param bool|null   $signed
     *
     * @return static
     */
    public function tag(string $message, string $annotation, ?string $commit = null, ?bool $signed = null): static;


    /**
     * Returns a list of all available tags
     *
     * @return IteratorInterface
     */
    public function list(): IteratorInterface;


    /**
     * Pushes all local tags to the specified remote
     *
     * @param string      $repository
     * @param string|null $branch
     *
     * @return static
     */
    public function push(string $repository, ?string $branch = null): static;


    /**
     * Deletes the specified tag
     *
     * @param string      $tag
     * @param string|null $remote
     *
     * @return static
     */
    public function delete(string $tag, ?string $remote = null): static;
}
