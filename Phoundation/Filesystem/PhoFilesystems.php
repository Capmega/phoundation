<?php

/**
 * Class PhoFilesystems
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Data\IteratorCore;
use Phoundation\Filesystem\Commands\LsBlk;


class PhoFilesystems extends IteratorCore
{
    /**
     * PhoFilesystems class constructor
     */
    public function __construct()
    {
        $this->source = LsBlk::new()->executeNoReturn()->getResults()->getSource();
    }


    /**
     * Returns a new PhoFilesystems object
     *
     * @param bool $devices_too
     *
     * @return static
     */
    public static function new(bool $devices_too = false): static
    {
        return new static($devices_too);
    }
}
