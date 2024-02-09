<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Enums\Interfaces\EnumDisplayModeInterface;


/**
 * Enum DisplayMode
 *
 * The different display modes for elements or element blocks
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum EnumDisplayMode: string implements EnumDisplayModeInterface
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


    /**
     * Returns the primary mode for the given mode which might be an alias
     *
     * @param EnumDisplayModeInterface $mode
     * @return EnumDisplayModeInterface
     */
    public static function getPrimary(EnumDisplayModeInterface $mode): EnumDisplayModeInterface
    {
        // Convert aliases
        return match ($mode) {
            EnumDisplayMode::white       => EnumDisplayMode::white,
            EnumDisplayMode::blue,
            EnumDisplayMode::info,
            EnumDisplayMode::notice,
            EnumDisplayMode::information => EnumDisplayMode::info,
            EnumDisplayMode::green,
            EnumDisplayMode::success     => EnumDisplayMode::success,
            EnumDisplayMode::yellow,
            EnumDisplayMode::warning,    => EnumDisplayMode::warning,
            EnumDisplayMode::red,
            EnumDisplayMode::error,
            EnumDisplayMode::exception,
            EnumDisplayMode::danger      => EnumDisplayMode::danger,
            EnumDisplayMode::plain,
            EnumDisplayMode::primary,
            EnumDisplayMode::secondary,
            EnumDisplayMode::tertiary,
            EnumDisplayMode::link,
            EnumDisplayMode::light,
            EnumDisplayMode::dark        => $mode,
            EnumDisplayMode::null,
            EnumDisplayMode::unknown     => EnumDisplayMode::null,
            default => throw new OutOfBoundsException(tr('Unknown mode ":mode" specified', [
                ':mode' => $mode
            ]))
        };
    }
}
