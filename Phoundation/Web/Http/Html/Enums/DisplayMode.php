<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Enums;

use Phoundation\Web\Http\Html\Interfaces\InterfaceDisplayMode;

/**
 * Enum DisplayMode
 *
 * The different display modes for elements or element blocks
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum DisplayMode: string implements InterfaceDisplayMode
{
    case white     = 'white';
    case success   = 'success';
    case info      = 'info';
    case warning   = 'warning';
    case danger    = 'danger';
    case primary   = 'primary';
    case secondary = 'secondary';
    case tertiary  = 'tertiary';
    case link      = 'link';
    case light     = 'light';
    case dark      = 'dark';
    case plain     = 'plain';
    case unknown   = 'unknown';
    case null      = '';

    // The following entries are aliases

    case blue        = 'blue';        // info
    case notice      = 'notice';      // info
    case information = 'information'; // info
    case green       = 'green';       // success
    case yellow      = 'yellow';      // warning
    case red         = 'red';         // danger
    case error       = 'error';       // danger
    case exception   = 'exception';   // danger
}
