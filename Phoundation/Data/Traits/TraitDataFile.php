<?php

/**
 * Trait TraitDataFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\FsFileInterface;


trait TraitDataFile
{
    /**
     * The file for this object
     *
     * @var FsFileInterface|null $file
     */
    protected ?FsFileInterface $file = null;


    /**
     * Returns the file
     *
     * @return FsFileInterface|null
     */
    public function getFile(): ?FsFileInterface
    {
        return $this->file;
    }


    /**
     * Sets the file
     *
     * @param FsFileInterface|null $file
     *
     * @return static
     */
    public function setFile(?FsFileInterface $file): static
    {
        $this->file = $file;

        return $this;
    }
}
