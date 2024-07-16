<?php

/**
 * Trait TraitMode
 *
 * Manages display modes for elements or element blocks
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Enums\EnumDisplayMode;

trait TraitMode
{
    /**
     * The type of mode for the element or element block
     *
     * @var EnumDisplayMode $mode
     */
    protected EnumDisplayMode $mode = EnumDisplayMode::primary;


    /**
     * Returns the type of mode for the element or element block
     *
     * @return EnumDisplayMode
     */
    public function getMode(): EnumDisplayMode
    {
        return $this->mode;
    }


    /**
     * Sets the type of mode for the element or element block
     *
     * @param EnumDisplayMode|string $mode
     *
     * @return static
     */
    public function setMode(EnumDisplayMode|string $mode): static
    {
        if (is_string($mode)) {
            $mode = EnumDisplayMode::from($mode);
        }
        // Ensure we have primary display mode
        $this->mode = $mode->getPrimary($mode);

        return $this;
    }
}