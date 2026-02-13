<?php

/**
 * Trait TraitDataObjectFiles
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openpath.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\PhoFilesInterface;


trait TraitDataObjectFiles
{
    /**
     * The path to use
     *
     * @var PhoFilesInterface|null $_files
     */
    protected ?PhoFilesInterface $_files = null;


    /**
     * Returns the path
     *
     * @return PhoFilesInterface|null
     */
    public function getFilesObject(): ?PhoFilesInterface
    {
        return $this->_files;
    }


    /**
     * Sets the path
     *
     * @param PhoFilesInterface|null $_files
     *
     * @return static
     */
    public function setFilesObject(?PhoFilesInterface $_files): static
    {
        $this->_files = $_files;
        return $this;
    }
}
