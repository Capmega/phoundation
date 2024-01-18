<?php

declare(strict_types=1);

namespace Phoundation\Core\Interfaces;


/**
 * Class ArrayableInterface
 *
 * This interface ensures that the __toArray() method is available
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package Phoundation\Core
 */
interface ArrayableInterface
{
    /**
     * Returns the contents of this object in an array
     *
     * @return array
     */
    public function __toArray(): array;
}
