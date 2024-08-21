<?php

/**
 * Enum EnumBootstrapColor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Templates\AdminLte
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumBootstrapColor: string
{
    case primary   = 'primary';
    case secondary = 'secondary';
    case success   = 'success';
    case danger    = 'danger';
    case warning   = 'warning';
    case info      = 'info';
    case light     = 'light';
    case dark      = 'dark';
    case white     = 'white';
}
