<?php

/**
 * Trait TraitDataDirectory
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

trait TraitDataDirectory
{
    use TraitDataDirectoryReadonly;


    /**
     * Sets the directory
     *
     * @param FsDirectoryInterface|null $directory
     * @param string|null               $prefix
     * @param bool                      $must_exist
     *
     * @return static
     */
    public function setDirectory(?FsDirectoryInterface $directory, string $prefix = null, bool $must_exist = true): static
    {
        $this->directory = $directory?->makeAbsolute($prefix, $must_exist);

        return $this;
    }
}
