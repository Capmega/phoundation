<?php

/**
 * Enum EnumJsonHtmlMethods
 *
 * This enum contains the possible client methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Json\Enums;

enum EnumJsonHtmlMethods: string
{
    case append  = 'append';
    case delete  = 'delete';
    case prepend = 'prepend';
    case replace = 'replace';
}
