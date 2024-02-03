<?php

declare(strict_types=1);

namespace Phoundation\Core;

use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Config;


/**
 * Class Tmp
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Tmp
 */
class Tmp
{
   /**
     * Clear all temp files and directories
     *
     * @return void
     */
    public static function clear(): void
    {
        Log::action(tr('Clearing all temporary files'), 3);

        // Delete all private temporary files
        Directory::new(DIRECTORY_TMP   , Restrictions::writable(DIRECTORY_TMP, tr('Clear tmp directories')))
            ->delete()
            ->ensure();

        // Delete all public temporary files
        Directory::new(DIRECTORY_PUBTMP, Restrictions::writable(DIRECTORY_PUBTMP, tr('Clear tmp directories')))
            ->delete()
            ->ensure();

        Log::success(tr('Cleared all temporary files'), 4);
    }


    /**
     * Clean up old temp files
     *
     * @param int|null $age_in_minutes
     * @return void
     */
    public static function clean(?int $age_in_minutes): void
    {
        if (!$age_in_minutes) {
            $age_in_minutes = Config::getInteger('tmp.clean.age', 1440);
        }

        Log::action(tr('Cleaning temporary files older than ":age" minutes', [
            ':age' => $age_in_minutes
        ]));

        Find::new()
            ->setFindPath(DIRECTORY_DATA . 'tmp/')
            ->setOlderThan($age_in_minutes)
            ->setExecute('rf {} -rf')
            ->executeNoReturn();
    }
}
