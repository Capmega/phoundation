<?php

namespace Phoundation\Date;



/**
 * Class DateTime
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Date
 */
class DateTime extends \DateTime
{
    /**
     * Wrapper around the PHP Datetime but with support for named formats, like "mysql"
     *
     * @param string $format
     * @return string
     */
    public function format(string $format): string
    {
        switch (strtolower($format)) {
            case 'mysql':
                $format = 'Y-m-d H:i:s';
                break;
        }

        return parent::format($format);
    }
}