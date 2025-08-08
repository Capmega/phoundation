<?php

/**
 * Enum EnumAnchorRenderRightsFail
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumAnchorRenderRightsFail: string
{
    case not     = 'not';
    case no_url  = 'no_url';
    case full    = 'full';
    case fail    = 'fail';
}
