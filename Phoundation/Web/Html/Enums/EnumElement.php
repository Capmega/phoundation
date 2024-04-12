<?php
/**
 * Enum EnumElement
 *
 * Contains a list of all HTML elements
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumElement: string
{
    case input    = 'input';
    case textarea = 'textarea';
    case select   = 'select';
    case button   = 'button';
    case div      = 'div';
    case span     = 'span';
    case label    = 'label';
    case p        = 'p';
}
