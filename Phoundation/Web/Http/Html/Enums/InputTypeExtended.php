<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Enums;

use Phoundation\Web\Http\Html\Interfaces\InputTypeExtendedInterface;


/**
 * Enum InputType
 *
 * The different available HTML <input> types
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum InputTypeExtended: string implements InputTypeExtendedInterface
{
    case dbid     = 'dbid';
    case float    = 'float';
    case integer  = 'integer';
    case natural  = 'natural';
    case name     = 'name';
    case username = 'username';
}
