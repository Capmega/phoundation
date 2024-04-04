<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Exception;


/**
 * Class NoTasksPendingExceptions
 *
 * This exception is thrown in case pending tasks are about to be executed but no tasks are pending
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class NoTasksPendingExceptions extends TasksException
{
}
