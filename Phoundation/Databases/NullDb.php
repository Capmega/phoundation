<?php

declare(strict_types=1);

namespace Phoundation\Databases;

use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Web\Requests\Interfaces\RequestInterface;


/**
 * Class NullDb
 *
 * This is the NullDb database, which will always return NULL values
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class NullDb implements DatabaseInterface
{
    /**
     * Do nothing, really
     *
     * @param string $key
     * @param string|null $namespace
     * @return mixed (but really, always NULL)
     */
    public function get(string $key, ?string $namespace = null): mixed
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
     * @param mixed $value
     * @param string $key
     * @param string|null $namespace
     * @return void
     */
    public function set(mixed $value, string $key, ?string $namespace = null): void
    {
    }


    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static
    {
        return $this;
    }
}