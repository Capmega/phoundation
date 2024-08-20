<?php

/**
 * Enum EnumHttpRequestMethod
 *
 * The possible HTTP request methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumHttpRequestMethod: string
{
    case get     = 'get';
    case post    = 'post';
    case head    = 'head';
    case put     = 'put';
    case delete  = 'delete';
    case connect = 'connect';
    case options = 'options';
    case trace   = 'trace';
    case patch   = 'patch';
}
