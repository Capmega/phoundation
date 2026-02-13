<?php

/**
 * Trait TraitDataObjectFile
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

use Phoundation\Filesystem\Interfaces\PhoFileInterface;


trait TraitDataObjectFile
{
    /**
     * The path to use
     *
     * @var PhoFileInterface|null $_file
     */
    protected ?PhoFileInterface $_file = null;


    /**
     * Returns the file object
     *
     * @return PhoFileInterface|null
     */
    public function getFileObject(): ?PhoFileInterface
    {
        return $this->_file;
    }


    /**
     * Sets the file object
     *
     * @param PhoFileInterface|null $_file
     *
     * @return static
     */
    public function setFileObject(?PhoFileInterface $_file): static
    {
        $this->_file = $_file;
        return $this;
    }
}
