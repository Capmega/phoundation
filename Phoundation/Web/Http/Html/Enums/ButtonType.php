<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Enums;

use Phoundation\Web\Http\Html\Interfaces\InterfaceButtonType;

/**
 * Enum ButtonTypes
 *
 * The different available HTML button types. These are InputTypes specifically to buttons
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum ButtonType: string implements InterfaceButtonType
{
    case button = 'button';
    case submit = 'submit';
    case reset = 'reset';
}
