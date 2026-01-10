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
}
