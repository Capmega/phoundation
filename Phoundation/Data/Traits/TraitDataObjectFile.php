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
     * @var PhoFileInterface|null $o_file
     */
    protected ?PhoFileInterface $o_file = null;


    /**
     * Returns the file object
     *
     * @return PhoFileInterface|null
     */
    public function getFileObject(): ?PhoFileInterface
    {
        return $this->o_file;
    }


    /**
     * Sets the file object
     *
     * @param PhoFileInterface|null $o_file
     *
     * @return static
     */
    public function setFileObject(?PhoFileInterface $o_file): static
    {
        $this->o_file = $o_file;
        return $this;
    }
}
