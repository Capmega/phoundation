<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

/**
 * Class New
 *
 * This interface simply ensures the availability of the class::new() method
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
interface NewObject
{
    public static function new(): static;
}