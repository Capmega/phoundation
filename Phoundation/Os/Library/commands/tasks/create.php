<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Os\Processes\Task;


/**
 * Script tasks/execute
 *
 * This script will display detailed information about the current framework, project, database ,etc.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setAutoComplete(Task::getAutoComplete());

CliDocumentation::setUsage('./pho tasks execute');

CliDocumentation::setHelp(Task::getHelpText());


// Create the new task
$task = Task::new()->apply()->save();


// Done!
if ($task->isSaved()) {
    Log::success(tr('Created new task ":task"', [':task' => $task->getLogId()]));

} else {
    Log::warning(tr('Did NOT creat new task'));
}
