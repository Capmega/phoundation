<?php

/**
 * Class DateTimeZone
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 */


declare(strict_types=1);

namespace Phoundation\Date;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Date\Exception\DateTimeException;
use Phoundation\Date\Exception\DateTimeZoneException;
use Phoundation\Date\Interfaces\DateTimeZoneInterface;
use Phoundation\Utils\Config;
use Throwable;


class DateTimeZone extends \DateTimeZone implements DateTimeZoneInterface
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
                case '':
                    // Default to system timezone

                case 'system':
                    $detected = Config::get('locale.timezone', 'UTC');
                    break;

                case 'server':
                    // The timezone which the server is using
                    $detected = static::getServerTimezone();
                    break;

                case 'user':
                    // no break

                case 'display':
                    // The timezone requested by the user
                    $detected = Session::getUserObject()
                                       ->getTimezone();
                    $detected = 'PDT';
                    break;

                default:
                    $detected = $timezone;
            }

            if (!$detected) {
                throw new DateTimeZoneException(tr('Failed to convert requested timezone ":timezone"', [
                    ':timezone' => $timezone,
                ]));
            }

            // Ensure the specified timezone is valid
            if (!in_array($detected, DateTimeZone::listIdentifiers())) {
                if (!array_key_exists(strtolower($detected), DateTimeZone::listAbbreviations())) {
                    throw new DateTimeException(tr('Detected timezone ":detected" (from specified ":timezone") is not supported', [
                        ':timezone' => $timezone,
                        ':detected' => $detected,
                    ]));
                }
            }

            $timezone = $detected;

        } else {
            // The specified timezone is a timezone object itself, get the timezone name from it
            $timezone = $timezone->getName();
        }

        parent::__construct($timezone);
    }


    /**
     * Returns the timezone for this server
     *
     * @return string
     */
    protected static function getServerTimezone(): string
    {
        static $timezone;

        if (empty($timezone)) {
            try {
                $timezone = Config::get('server.timezone', exec('date +%Z'));

            } catch (Throwable $e) {
                throw new DateTimeZoneException(tr('Failed to get server timezone'), $e);
            }
        }

        return $timezone;
    }


    /**
     * Returns a new DateTimeZone object
     *
     * @param \DateTimeZone|DateTimeZone|string|null $timezone
     *
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
