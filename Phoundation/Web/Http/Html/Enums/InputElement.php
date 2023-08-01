<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Enums;

use Phoundation\Web\Http\Html\Components\Interfaces\InputElementInterface;


/**
 * Enum InputElement
 *
 * The different available HTML <input> types
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum InputElement: string implements InputElementInterface
{
    case input    = 'input';
    case textarea = 'textarea';
    case select   = 'select';
}
