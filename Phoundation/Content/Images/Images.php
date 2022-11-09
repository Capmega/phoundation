<?php

namespace Phoundation\Content\Images;



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
class Images
{
    /**
     * Returns a Convert class to convert the specified image
     *
     * @param string $file
     * @return Convert
     */
    public static function convert(string $file): Convert
    {
        return new Convert($file);
    }
}