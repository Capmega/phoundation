<?php

/**
 * Enum EnumCacheGroups
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cache
 */


declare(strict_types=1);

namespace Phoundation\Cache\Enums;


enum EnumCacheGroups: string
{
    case autosuggest = 'cache-autosuggest';
    case dataentries = 'cache-dataentries';
    case html        = 'cache-html';
    case objects     = 'cache-objects';
    case values      = 'cache-values';
    case cache       = 'cache';
}
