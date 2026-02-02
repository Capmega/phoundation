<?php

/**
 * Enum EnumGitSelected
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Enums;

enum EnumGitSelected: string
{
    case branch   = 'branch';
    case tag      = 'tag';
    case detached = 'detached';
}
