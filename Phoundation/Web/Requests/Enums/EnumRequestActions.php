<?php

/**
 * Enum EnumRequestActions
 *
 * This enum contains all the possible types of actions that the Response object can take for a web request
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests\Enums;


enum EnumRequestActions: string
{
    case sent_content  = 'sent_content';
    case redirected    = 'redirected';
    case exception     = 'exception';
    case blocked       = 'blocked';
    case other         = 'other';
}
