<?php

declare(strict_types=1);

namespace Phoundation\Security\Incidents\Exception;

use Phoundation\Security\Exception\LuksException;
use Phoundation\Security\Incidents\Exception\Interfaces\IncidentsExceptionInterface;

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
class IncidentsException extends LuksException implements IncidentsExceptionInterface
{
}