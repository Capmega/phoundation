<?php

declare(strict_types=1);

namespace Phoundation\Cli\Exception;

use Throwable;

/**
 * Class ArgumentsException
 *
 * This exception is thrown in case of issues with command line arguments
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */
class CliArgumentsException extends CliException
{
    /**
     * ArgumentsException class constructor
     *
     * @param Throwable|array|string|null $messages
     * @param Throwable|null              $previous
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        parent::__construct($messages, $previous);
        $this->makeWarning();
    }
}
