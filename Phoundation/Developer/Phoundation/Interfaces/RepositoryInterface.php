<?php
namespace Phoundation\Developer\Phoundation\Interfaces;

interface RepositoryInterface {
    /**
     * Returns the repository path
     *
     * @return string|null
     */
    public function getPath(): ?string;

    /**
     * Returns true if this repository exist
     *
     * @return bool
     */
    public function exists(): bool;

    /**
     * Returns true if this repository can be read from
     *
     * @return bool
     */
    public function isReadable(): bool;

    /**
     * Returns true if this repository can be written to
     *
     * @return bool
     */
    public function isWritable(): bool;

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
    public function isPhoundationCore(): bool;

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
    public function isPhoundationPlugins(): bool;

    /**
     * Get the value of is_template
     *
     * @return bool
     */
    public function isPhoundationTemplates(): bool;

    /**
     * Returns an automatically generated name of the repository
     *
     * @return string
     */
    public function getName(): string;
}