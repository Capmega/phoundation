<?php

/**
 * Enum EnumAttachJavascript
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumAttachJavascript: string
{
    case bare   = 'bare';   // Will return the rendered Javascript as-is, without <script> tags
    case here   = 'here';   // Will return the rendered Javascript within <script> tags
    case header = 'header'; // Will attach this rendered Javascript to the header of the page
    case footer = 'footer'; // Will attach this rendered Javascript to the footer of the page
}
