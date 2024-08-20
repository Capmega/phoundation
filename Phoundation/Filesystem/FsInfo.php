<?php

/**
 * FsInfo class
 *
 * This class gathers and returns or displays file information
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Data\EntryCore;
use Phoundation\Data\Traits\TraitDataPath;
use Phoundation\Filesystem\Interfaces\FsInfoInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;


class FsInfo extends EntryCore implements FsInfoInterface
{
    use TraitDataPath {
        setPath as protected __setPath;
    }


    /**
     * FsPathInfo class constructor
     *
     * @param FsPathInterface $path
     */
    public function __construct(FsPathInterface $path)
    {
        $this->path = $path;

        $this->source = [
            'path'       => $path->getSource(),
            'type'       => $path->getTypeName(),
            'size'       => $path->getSize(),
            'binary'     => $path->isBinary(),
            'filesystem' => $path->getFilesystemObject()->getSource(),
            'encrypted'  => $path->isEncrypted(),
        ];
    }
}
