<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

use Phoundation\Web\Html\Enums\Interfaces\EnumInputTypeInterface;


/**
 * Enum EnumInputType
 *
 * The different available HTML <input> types
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum EnumInputType: string implements EnumInputTypeInterface
{
    // HTML input types
    case button         = 'button';
    case checkbox       = 'checkbox';
    case color          = 'color';
    case date           = 'date';
    case datetime_local = 'datetime-local';
    case email          = 'email';
    case file           = 'file';
    case hidden         = 'hidden';
    case image          = 'image';
    case month          = 'month';
    case number         = 'number';
    case password       = 'password';
    case radio          = 'radio';
    case range          = 'range';
    case reset          = 'reset';
    case search         = 'search';
    case submit         = 'submit';
    case tel            = 'tel';
    case text           = 'text';
    case time           = 'time';
    case url            = 'url';
    case week           = 'week';
    case select         = 'select';

    // Extended options
    case dbid             = 'dbid';
    case float            = 'float';
    case boolean          = 'boolean';
    case integer          = 'integer';
    case natural          = 'natural';
    case code             = 'code';
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
    case auto_suggest     = 'auto-suggest';
}
