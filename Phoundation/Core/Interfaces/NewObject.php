<?php

namespace Phoundation\Core\Interfaces;


/**
 * Class New
 *
 * This interface simply ensures the availability of the class::new() method
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
interface NewObject
{
    public static function new(): static;
}