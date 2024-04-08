<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Plugins\Plugin;
use Phoundation\Core\Plugins\Plugins;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;
use Phoundation\Utils\Strings;

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

CliDocumentation::setUsage('./pho system plugins enable PLUGIN [PLUGIN, PLUGIN, ...]
./pho system plugins disable -a');

CliDocumentation::setHelp('This command allows you to enable plugins


ARGUMENTS


[PLUGIN[, PLUGIN, PLUGIN, ...]]         The name of the plugin you wish to enable

[-A, --all]                             If specified instead of a plugin name, will enable all plugins');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->selectAll('plugins')->isOptional()->exclusiveOrArgument('all', ALL)->sanitizeForceArray()->each()->isName()
                     ->validate();


if (ALL) {
    // Get all plugins
    $plugin = Plugins::new()->load()->each(function ($plugin) {
        // Enable plugin
        Plugin::get($plugin)->enable();
    });

    // Done!
    Log::success(tr('All plugins have been enabled'));

} else {
    // Get specified plugins
    foreach ($argv['plugins'] as $plugin) {
        // Enable plugin
        Plugin::get($plugin)->enable();
    }

    // Done!
    Log::success(tr('Plugins ":plugins" have been enabled', [
        ':plugins' => Strings::force($argv['plugins'], ', '),
    ]));
}
