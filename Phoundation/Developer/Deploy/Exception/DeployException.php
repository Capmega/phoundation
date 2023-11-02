<?php

declare(strict_types=1);

namespace Phoundation\Developer\Deploy\Exception;

use Phoundation\Developer\Exception\DeveloperException;
use Throwable;


/**
 * Class DeployException
 *
 * This is the default exception to be thrown by the Developer\Deploy class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class DeployException extends DeveloperException
{
    /**
     * DeployException __constructor
     *
     * @param Throwable|array|string|null $messages The exception messages
     * @param Throwable|null $previous A previous exception, if available.
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        // Deploy exceptions are mostly state errors and as such by default warnings. No need to show a backtrace to
        // tell the user that they should git commit first, for example
        parent::__construct($messages, $previous);
        $this->makeWarning();
    }
}
