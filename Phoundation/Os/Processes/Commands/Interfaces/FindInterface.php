<?php

namespace Phoundation\Os\Processes\Commands\Interfaces;

use Phoundation\Filesystem\Interfaces\FilesInterface;
use Stringable;


/**
 * Class Find
 *
 * This class manages the "find" command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
interface FindInterface
{
    /**
     * Returns the available amount of memory
     *
     * @return FilesInterface
     */
    public function find(): FilesInterface;

    /**
     * Returns the path in which to find
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Sets the path in which to find
     *
     * @param Stringable|string $path
     * @return $this
     */
    public function setPath(Stringable|string $path): static;
}