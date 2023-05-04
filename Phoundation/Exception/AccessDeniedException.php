<?php

declare(strict_types=1);

namespace Phoundation\Exception;

/**
 * Class AccessDeniedException
 *
 * This exception is thrown when access to a certain system was denied
 *
 * @package Phoundation\Exception
 */
class AccessDeniedException extends Exception
{
    /**
     * The new target that should be executed because of this access denied
     *
     * @var string|int|null
     */
    protected string|int|null $new_target;


    /**
     * Returns the new target
     *
     * @return string|int|null
     */
    public function getNewTarget(): string|int|null
    {
        return $this->new_target;
    }


    /**
     * Sets the new target
     *
     * @param string|int|null $new_target
     * @return AccessDeniedException
     */
    public function setNewTarget(string|int|null $new_target): static
    {
        $this->new_target = $new_target;
        return $this;
    }
}
