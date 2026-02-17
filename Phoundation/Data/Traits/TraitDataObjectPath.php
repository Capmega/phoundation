<?php

/**
 * Trait TraitDataObjectPath
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

use Phoundation\Filesystem\Interfaces\PhoPathInterface;


trait TraitDataObjectPath
{
    /**
     * The path to use
     *
     * @var PhoPathInterface|null $_path
     */
    protected ?PhoPathInterface $_path = null;


    /**
     * Returns the path
     *
     * @return PhoPathInterface|null
     */
    public function getPathObject(): ?PhoPathInterface
    {
        return $this->_path;
    }


    /**
     * Sets the path
     *
     * @param PhoPathInterface|null $_path
     *
     * @return static
     */
    public function setPathObject(?PhoPathInterface $_path): static
    {
        $this->_path = $_path;
        return $this;
    }
}
