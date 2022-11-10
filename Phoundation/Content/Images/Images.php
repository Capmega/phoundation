<?php

namespace Phoundation\Content\Images;

use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands;
use Phoundation\Servers\Server;



/**
 * Class Images
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Content
 */
class Images extends Commands
{
    /**
     * Returns a Convert class to convert the specified image
     *
     * @param string $file
     * @return Convert
     */
    public function convert(string $file): Convert
    {
        return new Convert($file);
    }
}