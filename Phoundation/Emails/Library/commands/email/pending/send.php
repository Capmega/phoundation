<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Emails\Emails;
use Phoundation\Os\Processes\Commands\PhoCommand;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;


/**
 * Script email/send
 *
 * This script can send out emails
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-l,--limit' => true,
                                          '-r,--auto-restart' => false,
                                          '-b,--background' => false,
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho email send');

CliDocumentation::setHelp('This command will send out emails that are pending sending


ARGUMENTS


-l, --limit LIMIT                      The maximum number of mails to send

-r, --auto-restart                     If specified and there are more mails than the maximim specified limit, the
                                       command will automatically restart. Each restart will also pass along the
                                       --auto-restart so the process will continue with new processes each time the
                                       limit has reached until all emails have been sent

-b, --background                       If specified in combination with --auto-restart, the command will auto restart
                                       and continue as a background process');


// Validate the arguments
$count = 0;
$argv  = ArgvValidator::new()
                      ->select('-l,--limit', true)->isOptional(50)->isPositive()->isLessThan(5000)
                      ->select('-r,--auto-restart')->isOptional(false)->isBoolean()
                      ->select('-b,--background')->isOptional(false)->isBoolean()
                      ->validate();


// Load the pending emails
$emails = Emails::new();
$emails->getQueryBuilder()
       ->addSelect('*')
       ->addWhere('`status` IS NOT NULL AND `status` = "PENDING-SEND"')
       ->setLimit($argv['limit']);


// Send the pending emails
foreach ($emails->load() as $email) {
    $count++;
    $email->send();
}


// Should we restart?
if ($count) {
    Log::success(tr('Sent ":count" emails', [':count' => $count]));

    if ($count >= $argv['limit']) {
        if ($argv['auto_restart']) {
            if ($argv['background']) {
                Log::action(tr('Restarting send process as background process'));
                $method = EnumExecuteMethod::background;
            } else {
                Log::action(tr('Restarting send process as background process'));
                $method = EnumExecuteMethod::passthru;
            }

            // Restart the command
            PhoCommand::new('emails/send')
                      ->addArgument('--auto-restart')
                      ->addArgument($argv['background'] ? '--background' : null)
                      ->addArguments($argv['limit'] ? [
                          '--limit',
                          $argv['limit'],
                      ] : null)
                      ->execute($method);
        }
    }

} else {
    Log::success(tr('Found no pending emails'));
}
