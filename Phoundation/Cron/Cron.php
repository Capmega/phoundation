<?php

/**
 * Class Cron
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Cron;

use Phoundation\Core\Libraries\Libraries;


class Cron
{
    /**
     * Instructs the Libraries class to clear the cron cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Libraries::clearCronCache();
    }


    /**
     * Instructs the Libraries class to have each library rebuild its cron cache
     *
     * @return void
     */
    public static function rebuildCache(): void
    {
        Libraries::rebuildCronCache();
    }


}
