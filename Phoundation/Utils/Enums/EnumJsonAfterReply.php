<?php

/**
 * Enum EnumJsonAfterReply
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Enums;

enum EnumJsonAfterReply: string
{
    case die                     = 'die';
    case continue                = 'continue';
    case closeConnectionContinue = 'close_connection_continue';
}
