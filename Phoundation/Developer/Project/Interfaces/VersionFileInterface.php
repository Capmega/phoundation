<?php

namespace Phoundation\Developer\Project\Interfaces;

use Phoundation\Developer\Project\Enums\EnumVersionFileType;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Utils\Interfaces\VersionInterface;


interface VersionFileInterface extends VersionInterface
{
    /**
     * Sets the version file
     *
     * @param EnumVersionFileType|null $o_version_file
     *
     * @return static
     */
    public function setVersionFileTypeObject(?EnumVersionFileType $o_version_file): static;

    /**
     * Returns the version file
     *
     * @return EnumVersionFileType|null
     */
    public function getVersionFileTypeObject(): ?EnumVersionFileType;

    /**
     * Returns the file object
     *
     * @return PhoFileInterface|null
     */
    public function getFileObject(): ?PhoFileInterface;

    /**
     * Returns the auto_save flag
     *
     * @return bool The current $auto_save value
     */
    public function getAutoSave(): bool;

    /**
     * Sets the auto_save flag
     *
     * @param bool $auto_save The new $auto_save value
     *
     * @return static
     */
    public function setAutoSave(bool $auto_save): static;
}