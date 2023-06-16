<?php

namespace Phoundation\Date;

use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Session;


/**
 * Class DateTimeZone
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Date
 */
class DateTimeZone extends \DateTimeZone
{
    protected function getObject(): static
    {
        if (!is_object($timezone)) {
            switch ($timezone) {
                case 'system':
                    $timezone = Config::get('locale::timezone', 'UTC');
                    break;

                case 'user':
                    $timezone = Session::getUser()->getTimezone();
                    break;
            }

            // Ensure the specified timezone is valid
            if (!in_array($timezone, DateTimeZone::listAbbreviations())) {
                Log::warning(tr('Specified timezone ":timezone" is not compatible with PHP, falling back to UTC', [
                    ':timezone' => $timezone
                ]));

                $timezone = 'UTC';
            }

            $timezone = new DateTimeZone($timezone);
        }

    }
}