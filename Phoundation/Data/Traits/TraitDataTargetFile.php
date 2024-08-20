<?php

/**
 * Trait TraitDataTargetFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentarget.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\FsFileInterface;


trait TraitDataTargetFile
{
    /**
     * The source object
     *
     * @var FsFileInterface|null $target_file
     */
    protected ?FsFileInterface $target_file = null;


    /**
     * Returns the source object
     *
     * @return FsFileInterface
     */
    public function getTargetFile(): FsFileInterface
    {
        return $this->target_file;
    }


    /**
     * Sets the source object
     *
     * @param FsFileInterface|null $target_file
     *
     * @return static
     */
    public function setTargetFile(?FsFileInterface $target_file): static
    {
        $this->target_file = $target_file;

        return $this;
    }
}
