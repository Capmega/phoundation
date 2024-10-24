<?php

/**
 * Trait TraitDataFile
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

use Phoundation\Filesystem\Interfaces\PhoFileInterface;


trait TraitDataFile
{
    /**
     * The file for this object
     *
     * @var PhoFileInterface|null $file
     */
    protected ?PhoFileInterface $file = null;


    /**
     * Returns the file
     *
     * @return PhoFileInterface|null
     */
    public function getFile(): ?PhoFileInterface
    {
        return $this->file;
    }


    /**
     * Sets the file
     *
     * @param PhoFileInterface|null $file
     *
     * @return static
     */
    public function setFile(?PhoFileInterface $file): static
    {
        $this->file = $file;

        return $this;
    }
}
