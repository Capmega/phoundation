<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Branches\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;


interface BranchesInterface extends IteratorInterface
{
    /**
     * Returns whether only version branches should be loaded
     *
     * @return bool
     */
    public function getFilterVersions(): bool;

    /**
     * Sets whether only version branches should be loaded
     *
     * @param bool $filter If true, this Branches object will only load version branches
     *
     * @return static
     */
    public function setFilterVersions(bool $filter): static;

    /**
     * Returns whether only suffix branches should be loaded
     *
     * @return bool
     */
    public function getFilterSuffixes(): bool;

    /**
     * Sets whether only suffix branches should be loaded
     *
     * @param bool $filter If true, this Branches object will only load suffix branches
     *
     * @return static
     */
    public function setFilterSuffixes(bool $filter): static;

    /**
     * Returns true if the specified branch is a version branch with suffix
     *
     * @param string $branch               The branch to test
     * @param bool   $short_version [true] If true, will require a short version (MAJOR.MINOR) instead of a full version (MAJOR.MINOR.REVISION)
     *
     * @return bool
     */
    public static function isVersionOnly(string $branch, bool $short_version = true): bool;

    /**
     * Returns true if the specified branch is a version branch with suffix
     *
     * @param string $branch The branch to test
     * @param bool   $short_version [true] If true, will require a short version (MAJOR.MINOR) instead of a full version (MAJOR.MINOR.REVISION)
     *
     * @return bool
     */
    public static function isVersionWithSuffix(string $branch, bool $short_version = true): bool;
}
