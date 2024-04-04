<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Exception;


/**
 * Class TaskAlreadyExecutedException
 *
 * This exception is thrown in case a task is about to execute but it already has been executing
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class TaskAlreadyExecutedException extends TasksException
{
}
