<?php

/**
 * Trait TraitDataSourceFile
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


trait TraitDataSourceFile
{
    /**
     * The source object
     *
     * @var FsFileInterface|null $source_file
     */
    protected ?FsFileInterface $source_file = null;


    /**
     * Returns the source object
     *
     * @return FsFileInterface
     */
    public function getSourceFile(): FsFileInterface
    {
        return $this->source_file;
    }


    /**
     * Sets the source object
     *
     * @param FsFileInterface|null $source_file
     *
     * @return static
     */
    public function setSourceFile(?FsFileInterface $source_file): static
    {
        $this->source_file = $source_file;

        return $this;
    }
}
