<?php

/**
 * Class PhoInfo
 *
 * This class gathers and returns or displays file information
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Data\EntryCore;
use Phoundation\Data\Traits\TraitDataObjectPath;
use Phoundation\Filesystem\Interfaces\PhoInfoInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;


class PhoInfo extends EntryCore implements PhoInfoInterface
{
    use TraitDataObjectPath {
        setPath as protected __setPath;
    }


    /**
     * PhoInfo class constructor
     *
     * @param PhoPathInterface $path
     */
    public function __construct(PhoPathInterface $path)
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
