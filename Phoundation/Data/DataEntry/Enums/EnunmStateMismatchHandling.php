<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Enums;

use Phoundation\Data\DataEntry\Enums\Interfaces\EnunmStateMismatchHandlingInterface;


/**
 * Enum EnunmStateMismatchHandling
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
enum EnunmStateMismatchHandling: string implements EnunmStateMismatchHandlingInterface
{
    case restrict       = 'restrict';
    case allow_override = 'allow_override';
    case ignore         = 'ignore';
}
