<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\DataFile;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;


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
