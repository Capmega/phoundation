<?php

declare(strict_types=1);

namespace Phoundation\Utils\Enums;

use Phoundation\Utils\Enums\Interfaces\EnumJsonAfterReplyInterface;

enum EnumJsonAfterReply: string implements EnumJsonAfterReplyInterface
{
    case die                     = 'die';
    case continue                = 'continue';
    case closeConnectionContinue = 'close_connection_continue';
}
