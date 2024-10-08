<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Plugins\Plugin;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;


/**
 * Script system/plugins/modify script
 *
 * This script allows you to modify your registered plugins
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
                                      'arguments' => [
                                          '-p,--priority' => true,
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho system plugins modify PLUGIN [OPTIONS]');

CliDocumentation::setHelp('This command allows you to modify your registered plugins


ARGUMENTS


PLUGIN                                  The name of the plugin you wish to modify

-p, --priority PRIORITY                 The priority number for this plugin. The lower the number, the earlier it will
                                        be started up in the plugins startup phase');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('plugin')->isName()
                     ->select('-p,--priority', true)->isOptional()->isBetween(0, 100)
                     ->validate();


// Get plugin
$plugin = Plugin::load($argv['plugin']);


// Modify plugin
if ($argv['priority'] !== null) {
    $plugin->setPriority($argv['priority']);
}


// TODO Add more options that can be updated


// Save plugin
$plugin->save();


// Done!
if ($plugin->isSaved()) {
    Log::success(tr('Plugin ":plugin" has been updated', [
        ':plugin' => $plugin->getName(),
    ]));

} else {
    Log::warning(tr('Plugin ":plugin" was not modified', [
        ':plugin' => $plugin->getName(),
    ]));
}
