<?php

/**
 * Trait TraitDataObjectDirectoryReadonly
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


trait TraitDataObjectDirectoryReadonly
{
    /**
     * The directory for this object
     *
     * @var PhoDirectoryInterface|null $_directory
     */
    protected ?PhoDirectoryInterface $_directory = null;


    /**
     * Returns the directory
     *
     * @return PhoDirectoryInterface|null
     */
    public function getDirectoryObject(): ?PhoDirectoryInterface
    {
        return $this->_directory;
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
    protected function setDirectoryObject(?PhoDirectoryInterface $_directory, ?string $prefix = null, bool $must_exist = true): static
    {
        $this->_directory = $_directory?->makeAbsolute($prefix, $must_exist);
        return $this;
    }
}
