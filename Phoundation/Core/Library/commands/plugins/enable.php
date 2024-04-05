<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Plugins\Plugin;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;


/**
 * Script system/plugins/enable script
 *
 * This script allows you to enable registered plugins
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Core
 */
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => [
            'word'   => 'SELECT `name` FROM `core_plugins` WHERE `name` LIKE :word AND `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
            'noword' => 'SELECT `name` FROM `core_plugins` WHERE `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
        ],
    ],
]);

CliDocumentation::setUsage('./pho system plugins enable PLUGIN');

CliDocumentation::setHelp('This command allows you to enable plugins


ARGUMENTS


PLUGIN                                  The name of the plugin you wish to enable');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('plugin')->isName()
                     ->select('-p,--priority', true)->isOptional()->isBetween(0, 100)
                     ->validate();


// Get plugin
$plugin = Plugin::get($argv['plugin']);


// Disable plugin
$plugin->enable();


// Done!
Log::success(tr('Plugin ":plugin" has been enabled', [
    ':plugin' => $plugin->getName(),
]));
