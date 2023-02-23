<?php

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataBindAddress;
use Phoundation\Data\Traits\DataPath;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataTarget;
use Phoundation\Data\Traits\DataValue;


/**
 * Class Grep
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Grep extends Command
{
    use DataPath;
    use DataValue;



    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @return array
     */
    public function execute(): array
    {
        // Return results
        return $this->process
            ->clearArguments()
            ->setCommand('grep')
            ->addArgument($this->value)
            ->addArguments($this->path)
            ->executeReturnArray();
   }
}