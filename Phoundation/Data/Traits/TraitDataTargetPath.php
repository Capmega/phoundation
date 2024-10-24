<?php

/**
 * Trait TraitDataTargetPath
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentarget.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\PhoPathInterface;


trait TraitDataTargetPath
{
    /**
     * The target object
     *
     * @var PhoPathInterface|null $target_path
     */
    protected ?PhoPathInterface $target_path = null;


    /**
     * Returns the target object
     *
     * @return PhoPathInterface
     */
    public function getTargetPath(): PhoPathInterface
    {
        return $this->target_path;
    }


    /**
     * Sets the target object
     *
     * @param PhoPathInterface|null $target_path
     *
     * @return static
     */
    public function setTargetPath(?PhoPathInterface $target_path): static
    {
        $this->target_path = $target_path;

        return $this;
    }
}
