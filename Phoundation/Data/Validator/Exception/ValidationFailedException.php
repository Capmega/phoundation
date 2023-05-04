<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator\Exception;

use Throwable;


/**
 * Class ValidationFailedException
 *
 * This exception is thrown when a validator found validation failures
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class ValidationFailedException extends ValidatorException
{
    public function __construct(array|string $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null)
    {
        parent::__construct($messages, $data, $code, $previous);
        $this->makeWarning();
    }
}
