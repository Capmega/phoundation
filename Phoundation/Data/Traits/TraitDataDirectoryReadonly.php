<?php

/**
 * Trait TraitDataDirectoryReadonly
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


trait TraitDataDirectoryReadonly
{
    /**
     * The directory for this object
     *
     * @var PhoDirectoryInterface|null $directory
     */
    protected ?PhoDirectoryInterface $directory = null;


    /**
     * Returns the directory
     *
     * @return PhoDirectoryInterface|null
     */
    public function getDirectory(): ?PhoDirectoryInterface
    {
        return $this->directory;
    }
}
