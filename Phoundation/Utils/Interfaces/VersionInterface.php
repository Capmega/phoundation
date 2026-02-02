<?php

namespace Phoundation\Utils\Interfaces;

interface VersionInterface
{
    /**
     * Sets the source for this Version object
     *
     * @param string|int|null $source The source for this Version object
     *
     * @return static
     */
    public function setSource(string|int|null $source): static;


    /**
     * Increases the major version by the specified amount
     *
     * @param int $by_value [1] The amount to increase the major version by
     *
     * @return static
     */
    public function increaseMajor(int $by_value = 1): static;


    /**
     * Increases the minor version by the specified amount
     *
     * @param int $by_value [1] The amount to increase the minor version by
     *
     * @return static
     */
    public function increaseMinor(int $by_value = 1): static;


    /**
     * Increases the revision version by the specified amount
     *
     * @param int $by_value [1] The amount to increase the revision version by
     *
     * @return static
     */
    public function increaseRevision(int $by_value = 1): static;


    /**
     * Decreases the major version by the specified amount
     *
     * @param int $by_value [1] The amount to decrease the major version by
     *
     * @return static
     */
    public function decreaseMajor(int $by_value = 1): static;


    /**
     * Decreases the minor version by the specified amount
     *
     * @param int $by_value [1] The amount to decrease the minor version by
     *
     * @return static
     */
    public function decreaseMinor(int $by_value = 1): static;


    /**
     * Decreases the revision version by the specified amount
     *
     * @param int $by_value [1] The amount to decrease the revision version by
     *
     * @return static
     */
    public function decreaseRevision(int $by_value = 1): static;


    /**
     * Returns true if the specified version is higher than the current version
     *
     * @param VersionInterface|string $version               The version to compare to
     * @param bool                    $or_equal_to   [false] If true, will return true when the specified version is equal to this version
     * @param bool                    $short_version [false] If true will work with short versions (8.4) instead of long versions (8.4.3)
     *
     * @return bool
     */
    public function isHigherThan(VersionInterface|string $version, bool $or_equal_to = false, bool $short_version = false): bool;


    /**
     * Returns true if the specified version is lower than the current version
     *
     * @param VersionInterface|string $version               The version to compare to
     * @param bool                    $or_equal_to   [false] If true, will return true when the specified version is equal to this version
     * @param bool                    $short_version [false] If true will work with short versions (8.4) instead of long versions (8.4.3)
     *
     * @return bool
     */
    public function isLowerThan(VersionInterface|string $version, bool $or_equal_to = false, bool $short_version = false): bool;
}
