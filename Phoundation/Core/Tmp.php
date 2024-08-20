<?php

/**
 * Class Tmp
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Tmp
 */


declare(strict_types=1);

namespace Phoundation\Core;

use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Config;


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
        FsDirectory::new(DIRECTORY_TMP, FsRestrictions::getWritable(DIRECTORY_TMP))
                   ->delete()
                   ->ensure();

        // Delete all public temporary files
        FsDirectory::new(DIRECTORY_PUBTMP, FsRestrictions::getWritable(DIRECTORY_PUBTMP))
                   ->delete()
                   ->ensure();

        Log::success(tr('Cleared all temporary files'));
    }


    /**
     * Clean up old temp files
     *
     * @param int|null $age_in_minutes
     *
     * @return void
     */
    public static function clean(?int $age_in_minutes): void
    {
        if (!$age_in_minutes) {
            $age_in_minutes = Config::getInteger('tmp.clean.age', 1440);
        }
        Log::action(tr('Cleaning temporary files older than ":age" minutes', [
            ':age' => $age_in_minutes,
        ]));
        Find::new()
            ->setFindPath(DIRECTORY_DATA . 'tmp/')
            ->setOlderThan($age_in_minutes)
            ->setExecute('rf {} -rf')
            ->executeNoReturn();
    }
}
