<?php

declare(strict_types=1);

namespace Phoundation\Security\Incidents;

use Phoundation\Security\Incidents\Exception\Interfaces\SeverityInterface;


/**
 * Enum Severity
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 */
enum Severity: string implements SeverityInterface
{
    case unknown = 'unknown';
    case notice  = 'notice';
    case low     = 'low';
    case medium  = 'medium';
    case high    = 'high';
    case severe  = 'severe';
}
