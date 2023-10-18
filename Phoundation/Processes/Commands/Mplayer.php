<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataBindAddress;
use Phoundation\Data\Traits\DataFile;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataTarget;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Enum\EnumExecuteMethod;
use Phoundation\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Processes\Process;


/**
 * Class Mplayer
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Mplayer extends Command
{
    use DataFile;


    /**
     * Play the specified file
     *
     * @param bool $background
     * @return void
     */
    public function play(bool $background): void
    {
        // Build the process parameters, then execute
        $this->clearArguments()
             ->setRestrictions(Restrictions::default($this->restrictions, Restrictions::new(PATH_DATA . 'mplayer', true, 'audio')))
             ->setInternalCommand('mplayer')
             ->addArgument($this->file)
             ->execute($background ? EnumExecuteMethod::background : EnumExecuteMethod::noReturn);
    }
}
