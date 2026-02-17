<?php

/**
 * Trait TraitDataObjectVersionFileType
 *
 * Adds support for EnumVersionFileType $_version_file_type to your class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openpath.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Traits;

use Phoundation\Developer\Project\Enums\EnumVersionFileType;


trait TraitDataObjectVersionFileType
{
    /**
     * The path to use
     *
     * @var EnumVersionFileType|null $_version_file_type
     */
    protected ?EnumVersionFileType $_version_file_type = null;


    /**
     * Returns the version file
     *
     * @return EnumVersionFileType|null
     */
    public function getVersionFileTypeObject(): ?EnumVersionFileType
    {
        return $this->_version_file_type;
    }


    /**
     * Sets the version file
     *
     * @param EnumVersionFileType|null $_version_file
     *
     * @return static
     */
    public function setVersionFileTypeObject(?EnumVersionFileType $_version_file): static
    {
        $this->_version_file_type = $_version_file;
        return $this;
    }
}
