<?php

/**
 * Enum JsonAfterReply
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Utils\Enums;

enum JsonAfterReply: string
{
    case die                     = 'die';
    case continue                = 'continue';
    case closeConnectionContinue = 'close_connection_continue';
}
