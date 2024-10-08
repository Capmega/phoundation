<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Os\Processes\Commands\Command;
use Phoundation\Os\Processes\Commands\Pgrep;
use Phoundation\Os\Processes\Commands\Service;
use Phoundation\Os\Processes\Exception\MonitorException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Utils;
use Phoundation\Web\Html\Enums\EnumDisplayMode;


/**
 * Script monitor/service
 *
 * This script will monitor the specified service (by name) and alert and restart when it stops
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho monitor service PROCESS_NAME');

CliDocumentation::setHelp('This command will monitor the specified service (by name) and alert and restart it when it stops');


// Get the arguments
$argv = ArgvValidator::new()
                     ->select('service')->isVariable()->sanitizeTrim()->sanitizeLowercase()->isInArray([
                                                                                                           'apache',
                                                                                                           'mysql',
                                                                                                           'php',
                                                                                                           'redis',
                                                                                                           'mongo',
                                                                                                           'memcached',
                                                                                                       ])
                     ->select('-m,--minimum', true)->isOptional()->isNatural()
                     ->validate();


// Ensure that the process command has sudo privileges
Command::sudoAvailable('service', Restrictions::new('/sbin,/usr/sbin'), true);


// Get process ids
$pids = Pgrep::new()->do($argv['service']);


try {
    // Is it up?
    if (!$pids) {
        throw MonitorException::new(tr('The service ":service" no longer has active processes on this server', [
            ':service' => $argv['service'],
        ]))->makeWarning();
    }

    if (count($pids) < $argv['minimum']) {
        throw MonitorException::new(tr('The service ":service" has ":count" processes available, less than the minimum number of ":minimum"', [
            ':count' => count($pids),
            ':minimum' => $argv['minimum'],
            ':service' => $argv['service'],
        ]))->makeWarning();
    }


    // Get process ids
    $status = Service::new()->setServiceName($argv['service'])->status();

} catch (MonitorException $e) {
    // Oh crap, its down! Quick, say something funny!
    Log::warning($e->getMessage());
    Log::warning(tr('Trying to restart service'));

    $status = Service::new()->setServiceName($argv['service'])->restart()->status();
    $status = Arrays::keepMatchingValues($status, [
        'loaded',
        'active',
    ],                           Utils::MATCH_ANY | Utils::MATCH_CONTAINS | Utils::MATCH_CASE_INSENSITIVE);

    if (count($status) == 2) {
        Notification::new()
                    ->setMode(EnumDisplayMode::warning)
                    ->setRoles('administrator')
                    ->setTitle(tr('Restarted dead service'))
                    ->setMessage(tr('Service ":service" was down but successfully restarted', [':service' => $argv['service']]))
                    ->setDetails([':service' => $argv['service']])
                    ->send(true);
    } else {
        Notification::new()
                    ->setMode(EnumDisplayMode::error)
                    ->setRoles('administrator')
                    ->setTitle(tr('Restarted dead service'))
                    ->setMessage(tr('Service ":service" was down but successfully restarted', [':service' => $argv['service']]))
                    ->setDetails([':service' => $argv['service']])
                    ->send(true);
    }
}
