<?php

namespace Phoundation\Databases;



/**
 * Class NullDb
 *
 * This is the NullDb database, which will always return NULL values
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class NullDb
{
    /**
     * Do nothing, really
     *
     * @param string $key
     * @param string|null $namespace
     * @return null
     */
    public function get(string $key, ?string $namespace = null): null
    {
        return null;
    }



    /**
     * Do nothing, really
     *
     * @param string $key
     * @param string|null $namespace
     * @return void
     */
    public function delete(string $key, ?string $namespace = null): void
    {
    }



    /**
     * Do nothing, really
     *
     * @param int $delay
     */
    public function clear(int $delay = 0): void
    {
    }



    /**
     * Do nothing, really
     *
     * @param string $key
     * @param string|null $namespace
     * @return void
     */
    public function set($value, string $key, ?string $namespace = null): void
    {
    }
}