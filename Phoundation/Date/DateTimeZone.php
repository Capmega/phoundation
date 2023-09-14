<?php

declare(strict_types=1);

namespace Phoundation\Date;

use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;


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
    /**
     * Ensures we have a valid DateTimeZone object, even when "system" or "user" or a timezone name string was specified
     *
     * @param \DateTimeZone|DateTimeZone|string|null $timezone
     */
    public function __construct(\DateTimeZone|DateTimeZone|string|null $timezone)
    {
        if (!is_object($timezone)) {
            switch ($timezone) {
                case 'system':
                    $timezone = Config::get('locale::timezone', 'UTC');
                    break;

                case 'user':
                    // no break
                case 'display':
                    $timezone = Session::getUser()->getTimezone();
$timezone = 'PDT';
                    break;
            }

            if (!$timezone) {
                $timezone = Config::get('locale::timezone', 'UTC');
            }

            // Ensure the specified timezone is valid
            if (!in_array($timezone, DateTimeZone::listIdentifiers())) {
                if (!array_key_exists(strtolower($timezone), DateTimeZone::listAbbreviations())) {
                    Log::warning(tr('Specified timezone ":timezone" is not compatible with PHP, falling back to UTC', [
                        ':timezone' => $timezone
                    ]));

                    $timezone = 'UTC';
                }
            }

        } elseif (!($timezone instanceof DateTimeZone)) {
            $timezone = $timezone->getName();
        }

        parent::__construct($timezone);
    }


    /**
     * Returns a new DateTimeZone object
     *
     * @param \DateTimeZone|DateTimeZone|string|null $timezone
     * @return static
     */
    public static function new(\DateTimeZone|DateTimeZone|string|null $timezone): static
    {
        return new DateTimeZone($timezone);
    }


    /**
     * Returns a PHP DateTimeZone object from this Phoundation DateTimeZone object
     *
     * @return \DateTimeZone
     */
    public function getPhpDateTimeZone(): \DateTimeZone
    {
        return new \DateTimeZone($this->getName());
    }
}
