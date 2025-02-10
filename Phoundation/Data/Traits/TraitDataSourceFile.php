<?php

/**
 * Trait TraitDataSourceFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\PhoFileInterface;


trait TraitDataSourceFile
{
    /**
     * The source object
     *
     * @var PhoFileInterface|null $source_file
     */
    protected ?PhoFileInterface $source_file = null;


    /**
     * Returns the source object
     *
     * @return PhoFileInterface
     */
    public function getSourceFile(): PhoFileInterface
    {
        return $this->source_file;
    }


    /**
     * Sets the source object
     *
     * @param PhoFileInterface|null $source_file
     *
     * @return static
     */
    public function setSourceFile(?PhoFileInterface $source_file): static
    {
        $this->source_file = $source_file;
        return $this;
    }
}
