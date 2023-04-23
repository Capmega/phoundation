<?php

namespace Phoundation\Exception;

use Phoundation\Developer\Debug;
use Throwable;


/**
 * Class UnderConstructionException
 *
 * This exception will be thrown when code sections are under construction
 *
 * @package Phoundation\Exception
 */
class UnderConstructionException extends Exception
{
    public function __construct(array|string $messages = null, mixed $data = null, ?string $code = null, ?Throwable $previous = null)
    {
        $messages = tr(':location IS UNDER CONSTRUCTION', [':location' => Debug::currentLocation(1)]);
        $this->makeWarning();

        parent::__construct($messages, $data, $code, $previous);
    }
}
