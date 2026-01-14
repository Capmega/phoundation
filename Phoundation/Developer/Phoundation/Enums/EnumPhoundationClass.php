<?php

/**
 * Enum EnumPhoundationPlatform
 *
 * Contains the possible Phoundation repository platforms
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Phoundation\Enums;

enum EnumPhoundationClass: string
{
    case phoundation = 'phoundation';
    case project     = 'project';
    case cdn         = 'cdn';
}
