<?php

/**
 * Class ComposerException
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands\Exception;

use Throwable;

class ComposerException extends CommandsException
{
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null) {
        parent::__construct($messages, $previous);
        $this->makeWarning();
    }
}
