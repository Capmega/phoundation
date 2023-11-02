<?php

namespace Phoundation\Exception\Interfaces;


/**
 * Class AccessDeniedException
 *
 * This exception is thrown when access to a certain system was denied
 *
 * @package Phoundation\Exception
 */
interface AccessDeniedExceptionInterface
{
    /**
     * Returns the new target
     *
     * @return string|int|null
     */
    public function getNewTarget(): string|int|null;

    /**
     * Sets the new target
     *
     * @param string|int|null $new_target
     * @return static
     */
    public function setNewTarget(string|int|null $new_target): static;
}
