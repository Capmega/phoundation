<?php

namespace Phoundation\Utils\Enums;

use Phoundation\Utils\Enums\Interfaces\JsonAfterReplyInterface;

enum JsonAfterReply: string implements JsonAfterReplyInterface
{
    case die                     = 'die';
    case continue                = 'continue';
    case closeConnectionContinue = 'close_connection_continue';
}
