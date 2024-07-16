<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Icons\Interfaces;

use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;

/**
 * Icon class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
interface IconInterface extends ElementInterface
{
    /**
     * Returns the icon for this object
     *
     * @return string|null
     */
    public function getIcon(): ?string;


    /**
     * Sets the icon for this object
     *
     * @param string|null $icon
     * @param string      $subclass
     *
     * @return static
     */
    public function setIcon(?string $icon, string $subclass = ''): static;


    /**
     * @return string|null
     */
    public function render(): ?string;


    /**
     * Returns the type of mode for the element or element block
     *
     * @return EnumDisplayMode
     */
    public function getMode(): EnumDisplayMode;


    /**
     * Sets the type of mode for the element or element block
     *
     * @param EnumDisplayMode|string $mode
     *
     * @return static
     */
    public function setMode(EnumDisplayMode|string $mode): static;
}