<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Enums\DisplayMode;

/**
 * Mode trait
 *
 * Manages display modes for elements or element blocks
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait Mode
{
    /**
     * The type of mode for the element or element block
     *
     * @var DisplayMode $mode
     */
    protected DisplayMode $mode = DisplayMode::primary;


    /**
     * Returns the type of mode for the element or element block
     *
     * @return DisplayMode
     */
    public function getMode(): DisplayMode
    {
        return $this->mode;
    }


    /**
     * Sets the type of mode for the element or element block
     *
     * @param DisplayMode $mode
     * @return static
     */
    public function setMode(DisplayMode $mode): static {
        // Convert aliases
        $mode = match ($mode) {
            DisplayMode::white       => DisplayMode::white,
            DisplayMode::blue,
            DisplayMode::info,
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
            DisplayMode::dark,
            DisplayMode::null        => $mode,
            default                  => throw new OutOfBoundsException(tr('Unknown mode ":mode" specified', [
                ':mode' => $mode
            ]))
        };

        $this->mode = $mode;

        return $this;
    }
}