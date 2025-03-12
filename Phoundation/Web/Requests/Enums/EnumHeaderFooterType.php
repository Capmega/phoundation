<?php

/**
 * Enum EnumHeaderFooterType
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests\Enums;

enum EnumHeaderFooterType: string
{
    case html       = 'html';
    case link       = 'link';
    case meta       = 'meta';
    case javascript = 'javascript';
    case autodetect = 'autodetect';
}
