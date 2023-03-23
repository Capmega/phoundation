<?php

namespace Phoundation\Data\Traits;

use Phoundation\Core\Strings;


/**
 * Trait DataFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataFile
{
    /**
     * The file for this object
     *
     * @var string|null $file
     */
    protected ?string $file = null;



    /**
     * Returns the file
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }



    /**
     * Sets the file
     *
     * @param string|null $file
     * @return static
     */
    public function setFile(?string $file): static
    {
        $this->file = get_null($file);
        return $this;
    }
}