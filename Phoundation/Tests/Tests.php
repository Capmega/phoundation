<?php

/**
 * Class Tests
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Tests;

use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Os\Processes\Process;


class Tests
{
    /**
     * @return void
     */
    public static function startPhpUnitTests(): void
    {
        // First try loading all classes, plugins, and templates to see if there are any syntax errors
        Libraries::loadAllPhoundationClassesIntoMemory();
        Libraries::loadAllPluginClassesIntoMemory();
        // No update unit tests cache
        static::rebuildCache();
        // Now run unit tests
        Log::action(tr('Executing unit tests'));
        Process::new('phpunit')
               ->executePassthru();
    }


    /**
     * Rebuilds the test cache
     */
    public static function rebuildCache(): void
    {
        Libraries::rebuildTestsCache();
    }


    /**
     * Clears the test cache
     */
    public static function clearCache(): void
    {
        Libraries::clearTestsCache();
    }
}
