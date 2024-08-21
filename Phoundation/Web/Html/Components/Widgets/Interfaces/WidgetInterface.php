<?php

/**
 * Widget class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Interfaces;

interface WidgetInterface
{
    /**
     * Returns if this card is shown with gradient color or not
     *
     * @return bool
     */
    public function getGradient(): bool;

    /**
     * Sets if this card is shown with gradient color or not
     *
     * @param bool $gradient
     *
     * @return static
     */
    public function setGradient(bool $gradient): static;
}
