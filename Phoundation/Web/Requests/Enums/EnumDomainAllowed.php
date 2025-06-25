<?php

/**
 * Enum EnumDomainAllowed
 *
 * This enum contains options describing what is allowed in terms of redirect URLs
 *
 * ANY:       Any URL is allowed
 *
 * CURRENT:   Only URLs matching the current domain are allowed
 *
 * WHITELIST: URLs matching a defined whitelist are allowed
 *
 * @author    Harrison Macey <harrison@medinet.ca>
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests\Enums;

enum EnumDomainAllowed: string
{
    case any        = 'any';
    case current    = 'current';
    case whitelist  = 'whitelist';
}
