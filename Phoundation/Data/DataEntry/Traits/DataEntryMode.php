<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\Interfaces\DisplayModeInterface;
use Throwable;


/**
 * Trait DataEntryMode
 *
 * This trait contains methods for DataEntry objects that require a mode
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryMode
{
    /**
     * Returns the type of mode for the element or element block
     *
     * @return DisplayModeInterface
     */
    public function getMode(): DisplayModeInterface
    {
        return DisplayMode::from((string) $this->getSourceFieldValue('string', 'mode', 'primary'));
    }


    /**
     * Sets the type of mode for the element or element block
     *
     * @param DisplayModeInterface|string $mode
     * @return static
     */
    public function setMode(DisplayModeInterface|string $mode): static
    {
        if (is_string($mode)) {
            $mode = DisplayMode::from($mode);
        }

        // Ensure we have primary display mode
        return $this->setSourceValue('mode', $mode->getPrimary($mode)->value);
    }
}