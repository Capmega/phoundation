<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\Interfaces\EnumDisplayModeInterface;

/**
 * Trait TraitDataEntryMode
 *
 * This trait contains methods for DataEntry objects that require a mode
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryMode
{
    /**
     * Returns the type of mode for the element or element block
     *
     * @return EnumDisplayModeInterface
     */
    public function getMode(): EnumDisplayModeInterface
    {
        return EnumDisplayMode::from((string) $this->getValueTypesafe('string', 'mode', 'primary'));
    }


    /**
     * Sets the type of mode for the element or element block
     *
     * @param EnumDisplayModeInterface|string $mode
     *
     * @return static
     */
    public function setMode(EnumDisplayModeInterface|string $mode): static
    {
        if (is_string($mode)) {
            $mode = EnumDisplayMode::from($mode);
        }

        // Ensure we have primary display mode
        return $this->setValue('mode', $mode->getPrimary($mode)->value);
    }
}
