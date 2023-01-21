<?php

namespace Phoundation\Security;



/**
 * Enum Severity
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 */
enum Severity: string
{
    case notice = 'notice';
    case low = 'low';
    case medium = 'medium';
    case high = 'high';
    case severe = 'severe';
}
