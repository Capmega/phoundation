<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Enums;

use Phoundation\Web\Http\Html\Components\Interfaces\InputTypeExtendedInterface;


/**
 * Enum InputType
 *
 * The different available HTML <input> types
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum InputTypeExtended: string implements InputTypeExtendedInterface
{
    case url          = 'url';
    case dbid         = 'dbid';
    case float        = 'float';
    case boolean      = 'boolean';
    case integer      = 'integer';
    case natural      = 'natural';
    case code         = 'code';
    case file         = 'file';
    case path         = 'path';
    case name         = 'name';
    case phone        = 'phone';
    case phones       = 'phones';
    case username     = 'username';
    case description  = 'description';
}
