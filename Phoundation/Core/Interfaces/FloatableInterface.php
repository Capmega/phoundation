<?php

/**
 * Interface FloatableInterface
 *
 * This interface ensures that the __toFloat() method is available
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Interfaces;

interface FloatableInterface
{
    /**
     * Returns the contents of this object in a float value
     *
     * @return float
     */
    public function __toFloat(): float;
}
