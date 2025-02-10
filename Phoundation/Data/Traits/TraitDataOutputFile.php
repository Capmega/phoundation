<?php

/**
 * Trait TraitDataOutputFile
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


trait TraitDataOutputFile
{
    /**
     * The output_file for this object
     *
     * @var PhoFileInterface|null $output_file
     */
    protected ?PhoFileInterface $output_file = null;


    /**
     * Returns the output file
     *
     * @return PhoFileInterface|null
     */
    public function getOutputFile(): ?PhoFileInterface
    {
        return $this->output_file;
    }


    /**
     * Sets the output file
     *
     * @param PhoFileInterface|null $output_file
     *
     * @return static
     */
    public function setOutputFile(?PhoFileInterface $output_file): static
    {
        $this->output_file = $output_file;
        return $this;
    }
}
