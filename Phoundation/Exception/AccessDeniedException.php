<?php

declare(strict_types=1);

namespace Phoundation\Exception;

use Phoundation\Data\Traits\TraitDataNewTarget;
use Phoundation\Exception\Interfaces\AccessDeniedExceptionInterface;
use Throwable;

/**
 * Class AccessDeniedException
 *
 * This exception is thrown when access to a certain system was denied
 *
 * @package Phoundation\Exception
 */
class AccessDeniedException extends Exception implements AccessDeniedExceptionInterface
{
    use TraitDataNewTarget;

    /**
     * AccessDeniedException class constructor
     *
     * @param Throwable|array|string|null $messages
     * @param Throwable|null              $previous
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        parent::__construct($messages, $previous);
        $this->makeWarning();
    }
}
