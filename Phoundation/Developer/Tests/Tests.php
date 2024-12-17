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

namespace Phoundation\Developer\Tests;

use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Testers\HttpTester;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;
use Phoundation\Web\Http\Url;

class Tests
{
    /**
     * Start running PHPUnit Tests
     *
     * @return void
     */
    public static function unit(): void
    {
        // No update unit Tests cache
        static::rebuildCache();

        // First try loading all classes, plugins, and templates to see if there are any syntax errors
        Libraries::loadAllPhoundationClassesIntoMemory();
        Libraries::loadAllPluginClassesIntoMemory();

        try {
            Process::new(DIRECTORY_ROOT . 'vendor/bin/phpunit')
                   ->setExecutionDirectory(PhoDirectory::newRootObject())
                   ->addArguments(['--display-phpunit-deprecations'])
                   ->execute(EnumExecuteMethod::passthru);

        } catch (ProcessFailedException $e) {
            throw $e->makeWarning();
        }
    }


    /**
     * Starts the HTTP tests
     *
     * @return void
     */
    public static function http(): void
    {
        Log::information('Starting HTTP tests');

        HttpTester::new(Url::newCurrent())->execute();
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
