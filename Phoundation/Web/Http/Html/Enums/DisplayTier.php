<?php

namespace Phoundation\Web\Http\Html\Enums;

use Phoundation\Web\Http\Html\Interfaces\InterfaceDisplayTier;


/**
 * Enum DisplayTier
 *
 * The different display tiers for elements or element blocks
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum DisplayTier: string implements InterfaceDisplayTier
{
    case null = '';
    case xxs = 'xxs';
    case xs = 'xs';
    case sm = 'sm';
    case md = 'md';
    case lg = 'lg';
    case xl = 'xl';
    case xxl = 'xxl';
}
