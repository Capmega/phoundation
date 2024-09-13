<?php

/**
 * Class ObsoleteException
 *
 * This exception will be thrown when code sections are obsolete
 * to be beyound acceptable limits
 *
 * @author    Sven Olaf Oostenbrink
 * @copyright Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Exception
 */


declare(strict_types=1);

namespace Phoundation\Exception;

use Phoundation\Developer\FunctionCall;
use Throwable;


class ObsoleteException extends Exception
{
    public function __construct(Throwable|array|string|null $messages = null, ?Throwable $previous = null)
    {
        if (!$messages) {
            $messages = tr('The file ":file" is obsolete', [
                ':file' => FunctionCall::new()->getFile()
            ]);
        }

        parent::__construct($messages, $previous);
    }
}
