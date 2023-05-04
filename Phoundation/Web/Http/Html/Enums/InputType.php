<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Enums;

use Phoundation\Web\Http\Html\Interfaces\InterfaceInputType;

/**
 * Enum ButtonTypes
 *
 * The different available HTML <input> types
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum InputType: string implements InterfaceInputType
{
    case button = 'button';
    case checkbox = 'checkbox';
    case color = 'color';
    case date = 'date';
    case datetime_local = 'datetime-local';
    case email = 'email';
    case file = 'file';
    case hidden = 'hidden';
    case image = 'image';
    case month = 'month';
    case numeric = 'numeric';
    case password = 'password';
    case radio = 'radio';
    case range = 'range';
    case reset = 'reset';
    case search = 'search';
    case submit = 'submit';
    case tel = 'tel';
    case text = 'text';
    case time = 'time';
    case url = 'url';
    case week = 'week';
    case null = '';
}
