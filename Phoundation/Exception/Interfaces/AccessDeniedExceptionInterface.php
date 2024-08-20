<?php

/**
 * Class AccessDeniedException
 *
 * This exception is thrown when access to a certain system was denied
 *
 * @package Phoundation\Exception
 */


declare(strict_types=1);

namespace Phoundation\Exception\Interfaces;

use Phoundation\Security\Incidents\Exception\Interfaces\IncidentsExceptionInterface;

interface AccessDeniedExceptionInterface extends IncidentsExceptionInterface
{
}
