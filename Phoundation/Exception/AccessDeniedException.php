<?php

/**
 * Class AccessDeniedException
 *
 * This exception is thrown when access to a certain system was denied
 *
 * @author    Sven Olaf Oostenbrink
 * @copyright Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Exception
 */


declare(strict_types=1);

namespace Phoundation\Exception;

use Phoundation\Security\Incidents\Exception\IncidentsException;


class AccessDeniedException extends IncidentsException
{
}
