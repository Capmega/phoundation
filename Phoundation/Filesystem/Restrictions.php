<?php

namespace Phoundation\Filesystem;




use Phoundation\Core\Arrays;
use Phoundation\Exception\UnderConstructionException;

/**
 * Restrictions class
 *
 * This class manages file access restrictions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Restrictions
{
    /**
     * Internal store of all restrictions
     *
     * @var array $paths
     */
    protected array $paths = [];



    /**
     * Restrictions constructor
     *
     * @param string|array|null $paths
     * @param bool $write
     */
    public function __construct(string|array|null $paths, bool $write = false)
    {
        if ($paths) {
            $this->setPaths($paths, $write);
        }
    }



    /**
     * Set all paths for this restriction
     *
     * @param array|string $paths
     * @param bool $write
     * @return Restrictions
     */
    public function setPaths(array|string $paths, bool $write = false): Restrictions
    {
        foreach (Arrays::force($paths) as $path) {
            $this->addPath($path, $write);
        }

        return $this;
    }



    /**
     * Add new path for this restriction
     *
     * @param string $path
     * @param bool $write
     * @return Restrictions
     */
    public function addPath(string $path, bool $write = false): Restrictions
    {
        $this->paths[$path] = $write;
        return $this;
    }



    /**
     * @param string|array $patterns
     * @return void
     */
    public function check(string|array $patterns): void
    {
        foreach ($this->paths as $path => $write) {
throw new UnderConstructionException();
        }
    }
}