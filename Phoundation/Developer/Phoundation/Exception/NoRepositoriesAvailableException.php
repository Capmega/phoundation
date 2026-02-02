<?php

/**
 * Class NoRepositoriesAvailableException
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Phoundation\Exception;

use Throwable;

class NoRepositoriesAvailableException extends PhoundationException
{
    /**
     * NoRepositoriesAvailableException class constructor
     *
     * @param Throwable|array|string|null $messages
     * @param Throwable|null $previous
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        parent::__construct($messages, $previous);
        $this->makeWarning();
    }
}
