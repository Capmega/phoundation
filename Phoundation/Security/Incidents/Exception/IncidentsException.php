<?php

/**
 * Class IncidentsException
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */

declare(strict_types=1);

namespace Phoundation\Security\Incidents\Exception;

use Phoundation\Data\Traits\TraitDataNewTarget;
use Phoundation\Security\Exception\SecurityException;
use Phoundation\Security\Incidents\Exception\Interfaces\IncidentsExceptionInterface;
use Throwable;

class IncidentsException extends SecurityException implements IncidentsExceptionInterface
{
    use TraitDataNewTarget;

    /**
     * AccessDeniedException class constructor
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
