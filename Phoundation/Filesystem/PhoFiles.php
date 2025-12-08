<?php

/**
 * Class PhoFiles
 *
 * This class adds a constructor and static new method to the PhoFilesCore class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;


class PhoFiles extends PhoFilesCore implements PhoFilesInterface
{
    /**
     * PhoFiles class constructor
     *
     * @param PhoDirectoryInterface|null                 $o_parent_directory
     * @param mixed                                      $source
     * @param PhoRestrictionsInterface|array|string|null $restrictions
     */
    public function __construct(?PhoDirectoryInterface $o_parent_directory = null, mixed $source = null, PhoRestrictionsInterface|array|string|null $restrictions = null)
    {
        $this->o_parent_directory  = $o_parent_directory;
        $this->accepted_data_types = [PhoPathInterface::class];
        $this->o_restrictions      = $restrictions ?? $o_parent_directory?->getRestrictionsObject();

        if ($source) {
            $this->setSource($source);
        }
    }


    /**
     * Returns a new PhoFiles object
     *
     * @param PhoDirectoryInterface|null                 $parent_directory
     * @param mixed|null                                 $source
     * @param PhoRestrictionsInterface|array|string|null $restrictions
     *
     * @return static
     */
    public static function new(?PhoDirectoryInterface $parent_directory = null, mixed $source = null, PhoRestrictionsInterface|array|string|null $restrictions = null): static
    {
        return new static($parent_directory, $source, $restrictions);
    }


    /**
     * Returns a new PhoFiles object from the given source, or NULL
     *
     * @param mixed|null                                 $source
     * @param PhoRestrictionsInterface|array|string|null $restrictions
     *
     * @return PhoFiles|null
     */
    public static function newFromSourceOrNull(mixed $source = null, PhoRestrictionsInterface|array|string|null $restrictions = null): ?static
    {
        if ($source === null) {
            return null;
        }

        return static::newFromSource($source, $restrictions);
    }


    /**
     * Returns a new PhoFiles object from the given source
     *
     * @param mixed|null                                 $source
     * @param PhoRestrictionsInterface|array|string|null $restrictions
     *
     * @return static
     */
    public static function newFromSource(mixed $source = null, PhoRestrictionsInterface|array|string|null $restrictions = null): static
    {
        return new static(null, $source, $restrictions);
    }
}
