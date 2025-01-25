<?php

/**
 * Class Service
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 * @uses      TraitProcessVariables
 */


declare(strict_types=1);

namespace Phoundation\Os\Services;

use Phoundation\Os\Services\Interfaces\ServiceInterface;


class Service extends ServiceCore implements ServiceInterface
{
    /**
     * Returns a new Service class object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }
}
