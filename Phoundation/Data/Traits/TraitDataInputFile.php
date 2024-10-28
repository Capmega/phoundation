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

use Phoundation\Filesystem\Interfaces\PhoFileInterface;


trait TraitDataInputFile
{
    /**
     * The input_file for this object
     *
     * @var PhoFileInterface|null $input_file
     */
    protected ?PhoFileInterface $input_file = null;


    /**
     * Returns the input file
     *
     * @return PhoFileInterface|null
     */
    public function getInputFile(): ?PhoFileInterface
    {
        return $this->input_file;
    }


    /**
     * Sets the input file
     *
     * @param PhoFileInterface|null $input_file
     *
     * @return static
     */
    public function setInputFile(?PhoFileInterface $input_file): static
    {
        $this->input_file = $input_file;

        return $this;
    }
}
