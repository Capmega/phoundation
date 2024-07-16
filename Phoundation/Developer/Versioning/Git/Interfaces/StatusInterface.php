<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

interface StatusInterface
{
    /**
     * Returns a new Status object
     *
     * @param string $status
     *
     * @return static
     */
    public function new(string $status): static;


    /**
     * Returns the status string
     *
     * @return string
     */
    public function getStatus(): string;


    /**
     * Returns a readable status string
     *
     * @return string
     */
    public function getReadable(): string;


    /**
     * Returns if this file is new or not
     *
     * @return bool
     */
    public function isNew(): bool;


    /**
     * Returns if this file is modified or not
     *
     * @return bool
     */
    public function isModified(): bool;


    /**
     * Returns if this file is indexed or not
     *
     * @return bool
     */
    public function isIndexed(): bool;


    /**
     * Returns if this file is deleted or not
     *
     * @return bool
     */
    public function isDeleted(): bool;


    /**
     * Returns if this file is renamed or not
     *
     * @return bool
     */
    public function isRenamed(): bool;


    /**
     * Returns if this file is tracked or not
     *
     * @return bool
     */
    public function isTracked(): bool;
}