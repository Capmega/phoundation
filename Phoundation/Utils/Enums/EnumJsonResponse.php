<?php

/**
 * Enum EnumJsonResponse
 *
 * Contains the possible reply types for the Json::reply() call
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Enums;

enum EnumJsonResponse: string
{
    case ok       = 'ok';
    case error    = 'error';
    case signin   = 'signin';
    case reload   = 'reload';
    case redirect = 'redirect';
}
