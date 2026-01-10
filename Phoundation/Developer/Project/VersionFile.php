<?php

/**
 * Class VersionFile
 *
 * Version file handling class
 *
 * This class handles the project version files located in config/project/
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Project;

use Phoundation\Developer\Project\Enums\EnumVersionFile;
use Phoundation\Developer\Traits\TraitDataObjectVersionFile;
use Phoundation\Filesystem\PhoFileCore;
use Phoundation\Filesystem\PhoRestrictions;


class VersionFile extends PhoFileCore
{
    use TraitDataObjectVersionFile {
        setVersionFileObject as protected __setVersionFileObject;
    }


    /**
     * VersionFile class constructor
     */
    public function __construct(EnumVersionFile $o_version_file)
    {
        $this->setVersionFileObject($o_version_file);
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
        $this->__setVersionFileObject($o_version_file);
        return $this->setSource(DIRECTORY_ROOT . 'config/project/' . $this->o_version_file->value, PhoRestrictions::newConfig(true, 'project/'));
    }


    /**
     * Returns the version string from this version file
     *
     * @return string
     */
    public function getVersion(): string
    {

    }


    /**
     * Sets the version string from this version file
     *
     * @param string $version
     *
     * @return VersionFile
     */
    public function setVersion(string $version): static
    {

    }


    /**
     * Increases the major number by the specified amount
     *
     * @param int $amount
     *
     * @return static
     */
    public function increaseMajor(int $amount = 1): static
    {

    }


    /**
     * Save the version to the file
     *
     * @return static
     */
    public function save(): static
    {
        $this->putContents($this->getVersion());

        return $this;
    }
}
