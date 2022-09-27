<?php

namespace Phoundation\Core\Exception;

use Phoundation\Core\Arrays;
use Phoundation\Core\Debug;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\Exception;
use Throwable;



/**
 * Class CoreException
 *
 * This is the basic exception for all Phoundation Core classes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Core
 */
class CoreException extends Exception
{



    /**
     * CoreException __constructor
     *
     * @param array|string $messages The exception message
     * @param array $data [array] Data related to the exception. Should be a named array with elements that may be
     *      anything, string, array, object, resource, etc. The handler for this exception is assumed to know how to
     *      handle this data if it wants to do so
     * @param string|null $code The exception code (optional)
     * @param Throwable|null $previous A previous exception, if available.
     */
    public function __construct($messages, array $data = [], ?string $code = null, ?Throwable $previous = null)
    {
        $messages = Arrays::force($messages);
        $message = reset($messages);
        $message = Strings::force($message);

        $this->messages = $messages;
        $this->code = $code;
        $this->data = $data;

        parent::__construct($message, 0, $previous);

        if (Debug::enabled()) {
            // Always log CoreExceptions in debug mode
            Log::error($this);
        }
    }



}
