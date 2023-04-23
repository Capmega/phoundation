<?php

namespace Phoundation\Data\DataEntry\Enums;


/**
 * Enum StateMismatchHandling
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
enum StateMismatchHandling: string
{
    case restrict       = 'restrict';
    case allow_override = 'allow_override';
    case ignore         = 'ignore';
}
