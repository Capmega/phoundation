<?php

namespace Phoundation\Web\Http\Html\Enums;

use Phoundation\Web\Http\Html\Enums\Interfaces\EnumAudioPreloadInterface;


/**
 * Enum EnumAudioPreload
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
Enum EnumAudioPreload: string implements EnumAudioPreloadInterface
{
    case none     = 'none';
    case metadata = 'metadata';
    case auto     = 'auto';
}