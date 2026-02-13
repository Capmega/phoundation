<?php

/**
 * Trait TraitDataObjectDirectory
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


trait TraitDataObjectDirectory
{
    use TraitDataObjectDirectoryReadonly {
        setDirectoryObject as protected ___setDirectoryObject;
    }


    /**
     * Sets the directory
     *
     * @param PhoDirectoryInterface|null $_directory
     * @param string|null                $prefix
     * @param bool                       $must_exist
     *
     * @return static
     */
    public function setDirectoryObject(?PhoDirectoryInterface $_directory, ?string $prefix = null, bool $must_exist = true): static
    {
        return $this->___setDirectoryObject($_directory, $prefix, $must_exist);
    }
}
