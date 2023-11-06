<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums\Interfaces;


/**
 * Interface InterfaceDisplayMode
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface DisplayModeInterface
{
    /**
     * Sets the type of mode for the element or element block
     *
     * @param DisplayModeInterface $mode
     * @return static
     */
    public static function getPrimary(DisplayModeInterface $mode): DisplayModeInterface;
}