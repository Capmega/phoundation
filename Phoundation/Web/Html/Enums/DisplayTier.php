<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

use Phoundation\Web\Html\Enums\Interfaces\DisplayTierInterface;


/**
 * Enum DisplayTierInterface
 *
 * The different display tiers for elements or element blocks
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum DisplayTier: string implements DisplayTierInterface
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