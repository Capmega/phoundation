<?php

declare(strict_types=1);

namespace Phoundation\Utils;

use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Utils\Traits\TraitVersion;

/**
 * Class Version
 *
 * This class is a FsFileFileInterface class specifically for version files
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */
class Version
{
    use TraitVersion {
        __construct as protected ___construct;
    }

    /**
     * The file containing the version
     *
     * @var FsFileInterface $file
     */
    protected FsFileInterface $file;

    /**
     * The version from the version file
     *
     * @var string $version
     */
    protected string $version;


    /**
     * Version class constructor
     *
     * @param FsFileInterface|string       $file
     * @param FsRestrictionsInterface|null $restrictions
     */
    public function __construct(FsFileInterface|string $file, ?FsRestrictionsInterface $restrictions = null)
    {
        $this->file    = new FsFile($file, $restrictions);
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
