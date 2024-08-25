<?php

/**
 * Class NoLongerSupportedException
 *
 * This exception will be thrown when code sections are no longer supported
 *
 * @author    Sven Olaf Oostenbrink
 * @copyright Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Exception
 */


declare(strict_types=1);

namespace Phoundation\Exception;

use Phoundation\Developer\Debug;
use Throwable;


class NoLongerSupportedException extends Exception
{
    public function __construct(Throwable|array|string|null $messages = null, ?Throwable $previous = null)
    {
        if (!$messages) {
            $messages = tr(':location IS NO LONGER SUPPORTED', [':location' => Debug::currentLocation(1)]);
        }
        $this->makeWarning();
        parent::__construct($messages, $previous);
    }
}
