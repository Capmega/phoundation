<?php

/**
 * Trait TraitDataDirectory
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

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;


trait TraitDataDirectory
{
    use TraitDataDirectoryReadonly {
        setDirectoryObject as protected __setDirectoryObject;
    }


    /**
     * Sets the directory
     *
     * @param PhoDirectoryInterface|null $o_directory
     * @param string|null                $prefix
     * @param bool                       $must_exist
     *
     * @return static
     */
    public function setDirectoryObject(?PhoDirectoryInterface $o_directory, ?string $prefix = null, bool $must_exist = true): static
    {
        return $this->__setDirectoryObject($o_directory, $prefix, $must_exist);
    }
}
