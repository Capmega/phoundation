<?php

/**
 * Class Version
 *
 * This class handles version files
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Utils;

use Phoundation\Data\Traits\TraitDataVersion;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoFile;


class Version
{
    use TraitDataVersion {
        __construct as protected ___construct;
    }

    /**
     * The file containing the version
     *
     * @var PhoFileInterface $file
     */
    protected PhoFileInterface $file;

    /**
     * The version from the version file
     *
     * @var string $version
     */
    protected string $version;


    /**
     * Version class constructor
     *
     * @param PhoFileInterface|string       $file
     * @param PhoRestrictionsInterface|null $restrictions
     */
    public function __construct(PhoFileInterface|string $file, ?PhoRestrictionsInterface $restrictions = null)
    {
        $this->file    = new PhoFile($file, $restrictions);
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
