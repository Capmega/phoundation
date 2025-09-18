<?php

namespace Phoundation\Web\Requests\Restrictions\Interfaces;

use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;


interface RequestMethodRestrictionsInterface
{
    /**
     * Sets the specified request method to be required
     *
     * @param EnumHttpRequestMethod $method
     * @param bool                  $strict
     *
     * @return static
     */
    public function require(EnumHttpRequestMethod $method, bool $strict = false): static;


    /**
     * Sets the specified request method to be allowed
     *
     * @param EnumHttpRequestMethod $method
     * @param bool                  $strict
     *
     * @return static
     */
    public function allow(EnumHttpRequestMethod $method, bool $strict = false): static;


    /**
     * Sets the specified request method to be restricted
     *
     * @param EnumHttpRequestMethod $method
     * @param bool                  $strict
     *
     * @return static
     */
    public function restrict(EnumHttpRequestMethod $method, bool $strict = false): static;

    /**
     * Checks if all restrictions are satisfied, will throw an exception otherwise
     *
     * @return static
     */
    public function checkRestrictions(): static;
}
