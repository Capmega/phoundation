<?php

namespace Phoundation\Filesystem;




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
     * @var array $restrictions
     */
    protected array $restrictions = [];



    /**
     * Add new
     *
     * @param string $path
     * @param bool $write
     * @return Restrictions
     */
    public function add(string $path, bool $write = false): Restrictions
    {
    }



    /**
     * @param string|array $patterns
     * @return void
     */
    public function check(string|array $patterns): void
    {
        foreach ($this->restrictions as $path => $write) {
throw new UnderConstructionException();
        }
    }
}