<?php

namespace Phoundation\Data\Traits;

use Phoundation\Core\Strings;


/**
 * Trait DataPath
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataPath
{
    /**
     * The path for this object
     *
     * @var string $path
     */
    protected string $path;



    /**
     * Returns the source
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }



    /**
     * Sets the source
     *
     * @param string $path
     * @return static
     */
    public function setPath(string $path): static
    {
        $this->path = Strings::slash($path);
        return $this;
    }
}