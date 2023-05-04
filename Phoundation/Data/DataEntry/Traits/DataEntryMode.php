<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Interfaces\InterfaceDisplayMode;

/**
 * Trait DataEntryMode
 *
 * This trait contains methods for DataEntry objects that require a mode
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryMode
{
    /**
     * Returns the type of mode for the element or element block
     *
     * @return InterfaceDisplayMode
     */
    public function getMode(): InterfaceDisplayMode
    {
        return DisplayMode::from($this->getDataValue('mode'));
    }


    /**
     * Sets the type of mode for the element or element block
     *
     * @param InterfaceDisplayMode $mode
     * @return static
     */
    public function setMode(InterfaceDisplayMode $mode): static {
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

        return $this->setDataValue('mode', $mode->value);
    }
}