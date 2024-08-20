<?php

/**
 * Trait TraitDataSourcePath
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

use Phoundation\Filesystem\Interfaces\FsPathInterface;


trait TraitDataSourcePath
{
    /**
     * The target object
     *
     * @var FsPathInterface|null $source_path
     */
    protected ?FsPathInterface $source_path = null;


    /**
     * Returns the target object
     *
     * @return FsPathInterface
     */
    public function getSourcePath(): FsPathInterface
    {
        return $this->source_path;
    }


    /**
     * Sets the target object
     *
     * @param FsPathInterface|null $source_path
     *
     * @return static
     */
    public function setSourcePath(?FsPathInterface $source_path): static
    {
        $this->source_path = $source_path;

        return $this;
    }
}
