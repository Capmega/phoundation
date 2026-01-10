<?php

/**
 * Trait TraitDataObjectVersionFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openpath.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Developer\Traits;

use Phoundation\Developer\Project\Enums\EnumVersionFile;


trait TraitDataObjectVersionFile
{
    /**
     * The path to use
     *
     * @var EnumVersionFile|null $o_version_file
     */
    protected ?EnumVersionFile $o_version_file = null;


    /**
     * Returns the version file
     *
     * @return EnumVersionFile|null
     */
    public function getVersionFileObject(): ?EnumVersionFile
    {
        return $this->o_version_file;
    }


    /**
     * Sets the version file
     *
     * @param EnumVersionFile|null $o_version_file
     *
     * @return static
     */
    public function setVersionFileObject(?EnumVersionFile $o_version_file): static
    {
        $this->o_version_file = $o_version_file;
        return $this;
    }
}
