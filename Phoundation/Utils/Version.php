<?php

declare(strict_types=1);

namespace Phoundation\Utils;

use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;


/**
 * Class Version
 *
 * This class is a File class specifically for version files
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Version
{
    use TraitVersion {
        __construct as protected ___construct;
    }


    /**
     * The file containing the version
     *
     * @var FileInterface $file
     */
    protected FileInterface $file;

    /**
     * The version from the version file
     *
     * @var string $version
     */
    protected string $version;


    /**
     * Version class constructor
     *
     * @param FileInterface|string $file
     * @param RestrictionsInterface|null $restrictions
     */
    public function __construct(FileInterface|string $file, ?RestrictionsInterface $restrictions = null) {
        $this->file    = new File($file, $restrictions);
        $this->version = $this->file->getContentsAsString();

        $this->___construct($this->version);
    }


    /**
     * Save the version to the file
     *
     * @return static
     */
    public function save(): static
    {
        $this->file->putContents($this->getVersion());
        return $this;
    }
}
