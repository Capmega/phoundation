<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

use Phoundation\Web\Html\Components\Interfaces\EnumInputTypeExtendedInterface;


/**
 * Enum EnumInputTypeExtended
 *
 * The different available HTML <input> types
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum EnumInputTypeExtended: string implements EnumInputTypeExtendedInterface
{
    case url              = 'url';
    case dbid             = 'dbid';
    case float            = 'float';
    case boolean          = 'boolean';
    case integer          = 'integer';
    case natural          = 'natural';
    case code             = 'code';
    case file             = 'file';
    case path             = 'path';
    case name             = 'name';
    case phone            = 'phone';
    case phones           = 'phones';
    case username         = 'username';
    case variable         = 'variable';
    case description      = 'description';
    case array_json       = 'array_json';
    case array_serialized = 'array_serialized';
    case positiveInteger  = 'positive_integer';
    case negativeInteger  = 'negative_integer';
}
