<?php

/**
 * FsFiles class
 *
 * This class adds a constructor and static new method to the FsFilesCore class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsFilesInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Os\Processes\Commands\Zip;

class FsFiles extends FsFilesCore implements FsFilesInterface
{
    /**
     * FsFiles class constructor
     *
     * @param FsDirectoryInterface|null                 $parent
     * @param mixed                                     $source
     * @param FsRestrictionsInterface|array|string|null $restrictions
     */
    public function __construct(?FsDirectoryInterface $parent, mixed $source = null, FsRestrictionsInterface|array|string|null $restrictions = null)
    {
        $this->parent              = $parent;
        $this->accepted_data_types = [FsPathInterface::class];
        $this->restrictions        = FsRestrictions::getRestrictionsOrDefault($restrictions, $parent->getRestrictions());

        if ($source) {
            $this->setSource($source);
        }
    }


    /**
     * Returns a new FsFiles object
     *
     * @param FsDirectoryInterface|null                 $parent
     * @param mixed|null                                $source
     * @param FsRestrictionsInterface|array|string|null $restrictions
     *
     * @return static
     */
    public static function new(?FsDirectoryInterface $parent = null, mixed $source = null, FsRestrictionsInterface|array|string|null $restrictions = null): static
    {
        return new static($parent, $source, $restrictions);
    }
}
