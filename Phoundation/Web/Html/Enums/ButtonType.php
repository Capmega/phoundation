<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

use Phoundation\Web\Html\Enums\Interfaces\ButtonTypeInterface;


/**
 * Enum ButtonTypes
 *
 * The different available HTML button types. These are InputTypes specifically to buttons
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum ButtonType: string implements ButtonTypeInterface
{
    case button = 'button';
    case submit = 'submit';
    case reset = 'reset';
}