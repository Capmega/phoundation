<?php

/**
 * Class AccessDeniedException
 *
 * This exception is thrown when access to a certain system was denied
 *
 * @package Phoundation\Exception
 */

declare(strict_types=1);

namespace Phoundation\Exception;

use Phoundation\Exception\Interfaces\AccessDeniedExceptionInterface;
use Phoundation\Security\Incidents\Exception\IncidentsException;

class AccessDeniedException extends IncidentsException implements AccessDeniedExceptionInterface
{
}
