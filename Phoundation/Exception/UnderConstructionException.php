<?php

namespace Phoundation\Exception;

use Throwable;



/**
 * Class UnderConstructionException
 *
 * This exception will be thrown when code sections are under construction
 * to be beyound acceptable limits
 *
 * @package Phoundation\Exception
 */
class UnderConstructionException extends Exception
{
    public function __construct(array|string $messages = null, mixed $data = null, ?string $code = null, ?Throwable $previous = null)
    {
        if (!$messages) {
            $messages = tr('UNDER CONSTRUCTION');
        }

        parent::__construct($messages, $data, $code, $previous);
    }
}
