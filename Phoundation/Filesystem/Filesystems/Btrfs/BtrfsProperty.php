<?php

/**
 * Class BtrfsProperty
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Filesystems\Btrfs;

use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoPath;


class BtrfsProperty extends Btrfs {
    /**
     * Returns new static object
     *
     * @param PhoPathInterface|null $_path
     *
     * @return static
     */
    public static function new(?PhoPathInterface $_path = null): static
    {
        return new static ($_path);
    }
}
