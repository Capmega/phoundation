<?php

/**
 * Tests bootstrap file
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Tests
 */


declare(strict_types=1);

namespace Phoundation\Tests;

use Exception;
use Phoundation\Cli\CliCommand;
use Phoundation\Core\Core;
use Phoundation\Utils\Strings;
use Throwable;


try {
    include_once('./vendor/autoload.php');
    define('PHO_DIRECTORY', Strings::untilReverse($_SERVER['SCRIPT_FILENAME'], 'vendor'));
} catch (Throwable $e) {
    throw new Exception('Failed to start autoloader', previous: $e);
}

// Startup Phoundation Core
CliCommand::start();
Core::setUnitTestMode(true);
