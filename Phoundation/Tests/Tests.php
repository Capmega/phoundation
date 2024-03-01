<?php

declare(strict_types=1);

namespace Phoundation\Tests;


use Phoundation\Core\Libraries\Libraries;

/**
 * Class Tests
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Tests
{
    /**
     * @return void
     */
    public static function unit(): void
    {

    }


    /**
     * Clears the test cache
     */
    public static function clearCache(): void
    {
        Libraries::clearTestsCache();
    }


    /**
     * Rebuilds the test cache
     */
    public static function rebuildCache(): void
    {
        Libraries::rebuildTestsCache();
    }
}
