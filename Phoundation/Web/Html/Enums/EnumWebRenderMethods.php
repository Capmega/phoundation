<?php

/**
 * Enum EnumRenderMethods
 *
 * The different ways to render certain components, like TreeView
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumWebRenderMethods: string
{
    case javascript = 'javascript';
    case html       = 'html';
}
