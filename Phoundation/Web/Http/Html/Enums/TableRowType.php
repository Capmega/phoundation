<?php

namespace Phoundation\Web\Http\Html\Enums;

use Phoundation\Web\Http\Html\Enums\Interfaces\TableRowTypeInterface;


/**
 * Enum TableRowType
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
enum TableRowType: string implements TableRowTypeInterface
{
    case header = 'header';
    case row    = 'row';
    case footer = 'footer';
}