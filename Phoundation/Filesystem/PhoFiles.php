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

use Phoundation\Filesystem\Interfaces\PhoFilesInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;


class PhoFiles extends PhoFilesCore implements PhoFilesInterface
{
    /**
     * PhoFiles class constructor
     *
     * @param PhoPathInterface|null                      $_parent_path
     * @param mixed                                      $source
     * @param PhoRestrictionsInterface|array|string|null $_restrictions
     */
    public function __construct(?PhoPathInterface $_parent_path = null, mixed $source = null, PhoRestrictionsInterface|array|string|null $_restrictions = null)
    {
        $this->_parent_directory  = $_parent_path;
        $this->accepted_data_types = [PhoPathInterface::class];
        $this->_restrictions      = $_parent_path?->getRestrictionsObject()->addRestrictions($_restrictions) ?? PhoRestrictions::newFilesystemRoot();

        if ($source) {
            $this->setSource($source);
        }
    }


    /**
     * Returns a new PhoFiles object
     *
     * @param PhoPathInterface|null                      $_parent_path
     * @param mixed|null                                 $source
     * @param PhoRestrictionsInterface|array|string|null $_restrictions
     *
     * @return static
     */
    public static function new(?PhoPathInterface $_parent_path = null, mixed $source = null, PhoRestrictionsInterface|array|string|null $_restrictions = null): static
    {
        return new static($_parent_path, $source, $_restrictions);
    }


    /**
     * Returns a new PhoFiles object from the given source, or NULL
     *
     * @param mixed|null                                 $source
     * @param PhoRestrictionsInterface|array|string|null $_restrictions
     *
     * @return PhoFiles|null
     */
    public static function newFromSourceOrNull(mixed $source = null, PhoRestrictionsInterface|array|string|null $_restrictions = null): ?static
    {
        if ($source === null) {
            return null;
        }

        return static::newFromSource($source, $_restrictions);
    }


    /**
     * Returns a new PhoFiles object from the given source
     *
     * @param mixed|null                                 $source
     * @param PhoRestrictionsInterface|array|string|null $_restrictions
     *
     * @return static
     */
    public static function newFromSource(mixed $source = null, PhoRestrictionsInterface|array|string|null $_restrictions = null): static
    {
        return new static(null, $source, $_restrictions);
    }
}
