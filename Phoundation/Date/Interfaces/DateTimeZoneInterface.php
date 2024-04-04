<?php

declare(strict_types=1);

namespace Phoundation\Date\Interfaces;


use DateTimeZone;

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
interface DateTimeZoneInterface
{
    /**
     * Returns a PHP DateTimeZone object from this Phoundation DateTimeZone object
     *
     * @return \DateTimeZone
     */
    public function getPhpDateTimeZone(): DateTimeZone;
}
