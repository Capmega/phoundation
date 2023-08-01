<?php

namespace Phoundation\Core\Interfaces;


/**
 * Class Arrayable
 *
 * This interface ensures that the __toArray() method is available
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package Phoundation\Core
 */
interface Arrayable
{
    /**
     * Returns the contents of this object in an array
     *
     * @return array
     */
    public function __toArray(): array;
}