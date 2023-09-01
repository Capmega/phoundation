<?php

declare(strict_types=1);

namespace Phoundation\Processes\Exception;


/**
 * Class MonitorException
 *
 * This exception is thrown by process monitoring scripts, typically when a process is down
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class MonitorException extends ProcessesException
{
}