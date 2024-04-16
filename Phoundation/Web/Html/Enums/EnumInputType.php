<?php
/**
 * Enum EnumInputType
 *
 * The different available HTML <input> types
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumInputType: string
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
    case select         = 'select';
    case submit         = 'submit';
    case tel            = 'tel';
    case text           = 'text';
    case time           = 'time';
    case url            = 'url';
    case week           = 'week';

    // Extended options
    case array_json       = 'array_json';
    case array_serialized = 'array_serialized';
    case auto_suggest     = 'auto-suggest';
    case boolean          = 'boolean';
    case code             = 'code';
    case day              = 'day';
    case dbid             = 'dbid';
    case description      = 'description';
    case float            = 'float';
    case integer          = 'integer';
    case natural          = 'natural';
    case name             = 'name';
    case negativeInteger  = 'negative_integer';
    case path             = 'path';
    case phone            = 'phone';
    case phones           = 'phones';
    case positiveInteger  = 'positive_integer';
    case username         = 'username';
    case variable         = 'variable';
    case year             = 'year';
}
