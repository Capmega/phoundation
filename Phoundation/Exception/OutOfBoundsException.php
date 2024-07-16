<?php

/**
 * Class OutOfBoundsException
 *
 * This is the phoundation version of the PHP OutOfBoundsException class, thrown whenever a variable or data is found
 * to be beyound acceptable limits
 *
 * @package Phoundation\Exception
 */

declare(strict_types=1);

namespace Phoundation\Exception;

use Phoundation\Exception\Interfaces\OutOfBoundsExceptionInterface;

class OutOfBoundsException extends Exception implements OutOfBoundsExceptionInterface
{
}
