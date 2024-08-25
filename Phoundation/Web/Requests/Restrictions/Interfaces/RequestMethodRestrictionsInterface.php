<?php

namespace Phoundation\Web\Requests\Restrictions\Interfaces;

use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;


interface RequestMethodRestrictionsInterface
{
    /**
     * Sets the specified request method to be required
     *
     * @param EnumHttpRequestMethod $method
     *
     * @return static
     */
    public function require(EnumHttpRequestMethod $method): static;

    /**
     * Sets the specified request method to be allowed
     *
     * @param EnumHttpRequestMethod $method
     *
     * @return static
     */
    public function allow(EnumHttpRequestMethod $method): static;

    /**
     * Sets the specified request method to be restricted
     *
     * @param EnumHttpRequestMethod $method
     *
     * @return static
     */
    public function restrict(EnumHttpRequestMethod $method): static;

    /**
     * Checks if all restrictions are satisfied, will throw an exception otherwise
     *
     * @return static
     */
    public function checkRestrictions(): static;
}
