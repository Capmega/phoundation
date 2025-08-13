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
        setPathObject as protected __setPathObject;
    }


    /**
     * PhoInfo class constructor
     *
     * @param PhoPathInterface $o_path
     */
    public function __construct(PhoPathInterface $o_path)
    {
        $this->o_path = $o_path;

        $this->source = [
            'path'       => $o_path->getSource(),
            'type'       => $o_path->getTypeName(),
            'size'       => $o_path->getSize(),
            'binary'     => $o_path->isBinary(),
            'filesystem' => $o_path->getFilesystemObject()->getSource(),
            'encrypted'  => $o_path->isEncrypted(),
        ];
    }
}
