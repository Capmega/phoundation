<?php

/**
 * Trait TraitDataInputFile
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

use Phoundation\Filesystem\Interfaces\FsFileInterface;


trait TraitDataInputFile
{
    /**
     * The input_file for this object
     *
     * @var FsFileInterface|null $input_file
     */
    protected ?FsFileInterface $input_file = null;


    /**
     * Returns the input file
     *
     * @return FsFileInterface|null
     */
    public function getInputFile(): ?FsFileInterface
    {
        return $this->input_file;
    }


    /**
     * Sets the input file
     *
     * @param FsFileInterface|null $input_file
     *
     * @return static
     */
    public function setInputFile(?FsFileInterface $input_file): static
    {
        $this->input_file = $input_file;

        return $this;
    }
}
