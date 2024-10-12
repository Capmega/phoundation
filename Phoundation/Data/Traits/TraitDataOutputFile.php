<?php

/**
 * Trait TraitDataOutputFile
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


trait TraitDataOutputFile
{
    /**
     * The output_file for this object
     *
     * @var FsFileInterface|null $output_file
     */
    protected ?FsFileInterface $output_file = null;


    /**
     * Returns the output file
     *
     * @return FsFileInterface|null
     */
    public function getOutputFile(): ?FsFileInterface
    {
        return $this->output_file;
    }


    /**
     * Sets the output file
     *
     * @param FsFileInterface|null $output_file
     *
     * @return static
     */
    public function setOutputFile(?FsFileInterface $output_file): static
    {
        $this->output_file = $output_file;

        return $this;
    }
}
