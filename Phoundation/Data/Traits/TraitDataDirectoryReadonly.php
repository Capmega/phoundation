<?php

/**
 * Trait TraitDataDirectoryReadonly
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

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;

trait TraitDataDirectoryReadonly
{
    /**
     * The directory for this object
     *
     * @var FsDirectoryInterface|null $directory
     */
    protected ?FsDirectoryInterface $directory = null;


    /**
     * Returns the directory
     *
     * @return FsDirectoryInterface|null
     */
    public function getDirectory(): ?FsDirectoryInterface
    {
        return $this->directory;
    }
}
