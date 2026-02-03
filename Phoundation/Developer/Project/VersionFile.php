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

use Phoundation\Data\Traits\TraitDataBoolAutoSave;
use Phoundation\Data\Traits\TraitDataObjectFile;
use Phoundation\Developer\Project\Enums\EnumVersionFileType;
use Phoundation\Developer\Project\Interfaces\VersionFileInterface;
use Phoundation\Developer\Traits\TraitDataObjectVersionFileType;
use Phoundation\Developer\Traits\TraitStaticMethodNewWithEnumVersionFileType;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\VersionCore;


class VersionFile extends VersionCore implements VersionFileInterface
{
    use TraitDataBoolAutoSave;
    use TraitDataObjectFile {
        setFileObject as protected;
    }
    use TraitStaticMethodNewWithEnumVersionFileType;
    use TraitDataObjectVersionFileType {
        setVersionFileTypeObject as protected __setVersionFileObject;
    }


    /**
     * VersionFile class constructor
     *
     * @param EnumVersionFileType $o_version_file_type The version file to use
     */
    public function __construct(EnumVersionFileType $o_version_file_type, bool $auto_save = true)
    {
        $this->setVersionFileTypeObject($o_version_file_type)
             ->setAutoSave($auto_save);
    }


    /**
     * Sets the version file
     *
     * @param EnumVersionFileType|null $o_version_file
     *
     * @return static
     */
    public function setVersionFileTypeObject(?EnumVersionFileType $o_version_file): static
    {
        $o_file = PhoFile::new(DIRECTORY_ROOT . 'config/project/' . $o_version_file->value, PhoRestrictions::newConfig(true, 'project/'));

        return $this->__setVersionFileObject($o_version_file)
                    ->setFileObject($o_file)
                    ->setSource($o_file->getContentsAsString());
    }


    /**
     * Will save the version to the version file if auto save is enabled
     *
     * @return static
     */
    protected function autoSave(): static
    {
        if ($this->getAutoSave()) {
            $this->o_file->setContents($this->getSource());
        }

        return $this;
    }


    /**
     * Increases the specified section version by the specified amount
     *
     * @param int $by_value The amount to increase the section version by
     * @param int $section  The section for which to increase the value
     *
     * @return static
     */
    protected function increaseSectionDirectly(int $by_value, int $section): static
    {
        parent::increaseSectionDirectly($by_value, $section);
        return $this->autoSave();
    }


    /**
     * Decreases the specified section version by the specified amount
     *
     * @param int $by_value The amount to decrease the section version by
     * @param int $section  The section for which to decrease the value
     *
     * @return static
     */
    protected function decreaseSectionDirectly(int $by_value, int $section): static
    {
        parent::decreaseSectionDirectly($by_value, $section);
        return $this->autoSave();
    }


    /**
     * Sets the source for this Version object
     *
     * @param string|int|null $source The source for this Version object
     * @param bool            $short_version
     *
     * @return static
     */
    public function setSource(string|int|null $source, bool $short_version = false): static
    {
        parent::setSource($source, $short_version);
        return $this->autoSave();
    }
}
