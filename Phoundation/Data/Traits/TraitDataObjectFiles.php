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
     * @var PhoFilesInterface|null $o_files
     */
    protected ?PhoFilesInterface $o_files = null;


    /**
     * Returns the path
     *
     * @return PhoFilesInterface|null
     */
    public function getFilesObject(): ?PhoFilesInterface
    {
        return $this->o_files;
    }


    /**
     * Sets the path
     *
     * @param PhoFilesInterface|null $o_path
     *
     * @return static
     */
    public function setFilesObject(?PhoFilesInterface $o_path): static
    {
        $this->o_files = $o_path;
        return $this;
    }
}
