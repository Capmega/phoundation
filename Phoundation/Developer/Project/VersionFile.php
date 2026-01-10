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
use Phoundation\Utils\Traits\TraitDataObjectVersion;


class VersionFile extends PhoFileCore
{
    use TraitDataObjectVersion;
    use TraitDataObjectVersionFile {
        setVersionFileObject as protected __setVersionFileObject;
    }


    /**
     * VersionFile class constructor
     *
     * @param EnumVersionFile $o_version_file The version file to use
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
        return $this->__setVersionFileObject($o_version_file)
                    ->setSource(DIRECTORY_ROOT . 'config/project/' . $this->o_version_file->value, PhoRestrictions::newConfig(true, 'project/'))
                    ->setVersion($this->getContentsAsString())
                    ->addChangeEventHandler();
    }
}
