<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

use Phoundation\Web\Html\Enums\Interfaces\EnumAudioPreloadInterface;


/**
 * Enum EnumAudioPreload
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Web
 */
enum EnumAudioPreload: string implements EnumAudioPreloadInterface
{
    case none     = 'none';
    case metadata = 'metadata';
    case auto     = 'auto';
}
