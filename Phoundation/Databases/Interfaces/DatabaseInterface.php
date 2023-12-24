<?php

declare(strict_types=1);

namespace Phoundation\Databases\Interfaces;


/**
 * Interface Database
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
interface DatabaseInterface
{
    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static;
}
