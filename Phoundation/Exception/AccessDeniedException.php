<?php

declare(strict_types=1);

namespace Phoundation\Exception;

use Phoundation\Exception\Interfaces\AccessDeniedExceptionInterface;


/**
 * Class AccessDeniedException
 *
 * This exception is thrown when access to a certain system was denied
 *
 * @package Phoundation\Exception
 */
class AccessDeniedException extends Exception implements AccessDeniedExceptionInterface
{
}
