<?php

declare(strict_types=1);

namespace Phoundation\Network\Proxies;


/**
 * Class Proxy
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Network
 */
class Proxy
{
    public function __construct()
    {

    }


    /**
     * Returns a new Proxy object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }
}