<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Exception\Interfaces;

interface AuthenticationExceptionInterface
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
     *
     * @return static
     */
    public function setNewTarget(string|int|null $new_target): static;
}
