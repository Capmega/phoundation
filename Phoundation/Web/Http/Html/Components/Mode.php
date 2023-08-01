<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\Interfaces\DisplayModeInterface;


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
     * @var DisplayModeInterface $mode
     */
    protected DisplayModeInterface $mode = DisplayMode::primary;


    /**
     * Returns the type of mode for the element or element block
     *
     * @return DisplayModeInterface
     */
    public function getMode(): DisplayModeInterface
    {
        return $this->mode;
    }


    /**
     * Sets the type of mode for the element or element block
     *
     * @param DisplayModeInterface|string $mode
     * @return static
     */
    public function setMode(DisplayModeInterface|string $mode): static {
        if (is_string($mode)) {
            $mode = DisplayMode::from($mode);
        }

        // Ensure we have primary display mode
        $this->mode = $mode->getPrimary($mode);
        return $this;
    }
}