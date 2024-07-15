<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Processes\Exception\NoTasksPendingExceptions;
use Phoundation\Os\Processes\Exception\TaskAlreadyExecutedException;
use Phoundation\Os\Processes\Task;
use Phoundation\Os\Processes\Tasks;


/**
 * Command workers/execute
 *
 * This script will execute a parent worker that will execute its task through multiple parallel child workers
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

CliDocumentation::setUsage('./pho workers execute');

CliDocumentation::setHelp('This command executes either a specific task (if specified) or will try to execute all 
pending tasks that should be executed


ARGUMENTS


[-t,--task TASK]                        The specific task code to execute');

CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-t,--task' => [
                                              'word'   => function ($word) { return Tasks::new()->autoCompleteFind($word); },
                                              'noword' => function () { return Tasks::new()->autoCompleteFind(); },
                                          ],
                                      ],
                                  ]);


// Get arguments
$argv = ArgvValidator::new()
                     ->select('-t,--task', true)->isOptional()->isUuid()
                     ->validate();


// Execute the task(s)
if ($argv['task']) {
    try {
        // Execute a specific task
        $task = Task::load($argv['task'])->execute();

    } catch (TaskAlreadyExecutedException) {
        throw TaskAlreadyExecutedException::new(tr('Task ":task" has already been executed', [
            ':task' => $argv['task'],
        ]))->makeWarning();
    }

} else {
    try {
        // Try to execute all pending tasks
        Tasks::new()->load()->execute();

    } catch (NoTasksPendingExceptions $e) {
        Log::warning($e->getMessage());
    }
}
