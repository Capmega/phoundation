<?php

/**
 * Interface IntegerableInterface
 *
 * This interface ensures that the __toInteger() method is available
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Interfaces;

interface IntegerableInterface
{
    /**
     * Returns the contents of this object in an integer value
     *
     * @return int
     */
    public function __toInteger(): int;
}
