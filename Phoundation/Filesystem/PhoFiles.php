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
     * @param PhoPathInterface|null                      $o_parent_path
     * @param mixed                                      $source
     * @param PhoRestrictionsInterface|array|string|null $o_restrictions
     */
    public function __construct(?PhoPathInterface $o_parent_path = null, mixed $source = null, PhoRestrictionsInterface|array|string|null $o_restrictions = null)
    {
        $this->o_parent_directory  = $o_parent_path;
        $this->accepted_data_types = [PhoPathInterface::class];
        $this->o_restrictions        = $o_restrictions ?? $o_parent_path?->getRestrictionsObject();

        if ($source) {
            $this->setSource($source);
        }
    }


    /**
     * Returns a new PhoFiles object
     *
     * @param PhoPathInterface|null                      $o_parent_path
     * @param mixed|null                                 $source
     * @param PhoRestrictionsInterface|array|string|null $o_restrictions
     *
     * @return static
     */
    public static function new(?PhoPathInterface $o_parent_path = null, mixed $source = null, PhoRestrictionsInterface|array|string|null $o_restrictions = null): static
    {
        return new static($o_parent_path, $source, $o_restrictions);
    }


    /**
     * Returns a new PhoFiles object from the given source, or NULL
     *
     * @param mixed|null                                 $source
     * @param PhoRestrictionsInterface|array|string|null $o_restrictions
     *
     * @return PhoFiles|null
     */
    public static function newFromSourceOrNull(mixed $source = null, PhoRestrictionsInterface|array|string|null $o_restrictions = null): ?static
    {
        if ($source === null) {
            return null;
        }

        return static::newFromSource($source, $o_restrictions);
    }


    /**
     * Returns a new PhoFiles object from the given source
     *
     * @param mixed|null                                 $source
     * @param PhoRestrictionsInterface|array|string|null $o_restrictions
     *
     * @return static
     */
    public static function newFromSource(mixed $source = null, PhoRestrictionsInterface|array|string|null $o_restrictions = null): static
    {
        return new static(null, $source, $o_restrictions);
    }
}
