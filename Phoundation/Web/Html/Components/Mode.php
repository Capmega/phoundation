<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\Interfaces\EnumDisplayModeInterface;


/**
 * Mode trait
 *
 * Manages display modes for elements or element blocks
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait Mode
{
    /**
     * The type of mode for the element or element block
     *
     * @var EnumDisplayModeInterface $mode
     */
    protected EnumDisplayModeInterface $mode = EnumDisplayMode::primary;


    /**
     * Returns the type of mode for the element or element block
     *
     * @return EnumDisplayModeInterface
     */
    public function getMode(): EnumDisplayModeInterface
    {
        return $this->mode;
    }


    /**
     * Sets the type of mode for the element or element block
     *
     * @param EnumDisplayModeInterface|string $mode
     * @return static
     */
    public function setMode(EnumDisplayModeInterface|string $mode): static {
        if (is_string($mode)) {
            $mode = EnumDisplayMode::from($mode);
        }

        // Ensure we have primary display mode
        $this->mode = $mode->getPrimary($mode);
        return $this;
    }
}