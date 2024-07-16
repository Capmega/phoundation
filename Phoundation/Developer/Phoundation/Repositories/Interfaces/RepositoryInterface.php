<?php

declare(strict_types=1);

namespace Phoundation\Developer\Phoundation\Repositories\Interfaces;

use Phoundation\Developer\Enums\EnumRepositoryType;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;

interface RepositoryInterface extends FsDirectoryInterface
{
    /**
     * Returns true if this repository is a Phoundation repository
     *
     * @return bool
     */
    public function isRepository(): bool;

   /**
     * Returns true if this repository is a phoundation project
     *
     * @return bool
     */
    public function isCore(): bool;

    /**
     * Returns if this is a Phoundation project, so NOT a repository
     *
     * @return bool
     */
    public function isPhoundationProject(): bool;

    /**
     * Get the value of is_plugin
     *
     * @return bool
     */
    public function isPlugins(): bool;

    /**
     * Get the value of is_template
     *
     * @return bool
     */
    public function isTemplates(): bool;

    /**
     * Returns an automatically generated name of the repository
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the type of Phoundation repository
     *
     * @return EnumRepositoryType|null
     */
    public function getRepositoryType(): ?EnumRepositoryType;

    /**
     * Returns true if this repository is of the specified type
     *
     * @param EnumRepositoryType $repository_type
     * @return bool
     */
    public function isRepositoryType(EnumRepositoryType $repository_type): bool;
}
