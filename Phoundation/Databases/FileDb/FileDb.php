<?php

/**
 * Class FileDb
 *
 * This is the FileDb database, which will store all data in files
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\FileDb;

use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;


class FileDb implements DatabaseInterface
{
    /**
     * Do nothing, really
     *
     * @param string|float|int|null $key
     * @param callable|null         $cache_callback
     *
     * @return mixed (but really, always NULL)
     */
    public function get(string|float|int|null $key, ?callable $cache_callback = null): mixed
    {
        return null;
    }


    /**
     * Do nothing, really
     *
     * @param string      $key
     * @param string|null $namespace
     *
     * @return void
     */
    public function delete(string $key, ?string $namespace = null): void {}


    /**
     * Do nothing, really
     *
     * @param int $delay
     */
    public function clear(int $delay = 0): void {}


    /**
     * Do nothing, really
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     *
     * @return static
     */
    public function set(mixed $value, string|float|int|null $key): static
    {
        return $this;
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


    /**
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function import(PhoFileInterface $file): static
    {
        return $this;
    }


    /**
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function export(PhoFileInterface $file): static
    {
        return $this;
    }


    /**
     * Do nothing, really
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return true;
    }
}
