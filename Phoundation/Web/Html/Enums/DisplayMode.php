<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Enums\Interfaces\DisplayModeInterface;


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
enum DisplayMode: string implements DisplayModeInterface
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
     * @param DisplayModeInterface $mode
     * @return DisplayModeInterface
     */
    public static function getPrimary(DisplayModeInterface $mode): DisplayModeInterface
    {
        // Convert aliases
        return match ($mode) {
            DisplayMode::white       => DisplayMode::white,
            DisplayMode::blue,
            DisplayMode::info,
            DisplayMode::notice,
            DisplayMode::information => DisplayMode::info,
            DisplayMode::green,
            DisplayMode::success     => DisplayMode::success,
            DisplayMode::yellow,
            DisplayMode::warning,    => DisplayMode::warning,
            DisplayMode::red,
            DisplayMode::error,
            DisplayMode::exception,
            DisplayMode::danger      => DisplayMode::danger,
            DisplayMode::plain,
            DisplayMode::primary,
            DisplayMode::secondary,
            DisplayMode::tertiary,
            DisplayMode::link,
            DisplayMode::light,
            DisplayMode::dark        => $mode,
            DisplayMode::null,
            DisplayMode::unknown     => DisplayMode::null,
            default => throw new OutOfBoundsException(tr('Unknown mode ":mode" specified', [
                ':mode' => $mode
            ]))
        };
    }
}
