<?php

/**
 * Enum EnumVersionFile
 *
 * This enum contains all the possible Phoundation version files
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Project\Enums;

enum EnumVersionFileType: string
{
    case phoundation = 'phoundation';
    case project     = 'project';
    case cdn         = 'cdn';
}
