<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\TraitDataFile;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;

/**
 * Class Mplayer
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class Mplayer extends Command
{
    use TraitDataFile;

    /**
     * Play the specified file
     *
     * @param bool $background
     *
     * @return void
     */
    public function play(bool $background): void
    {
        // Build the process parameters, then execute
        $this->clearArguments()
             ->setRestrictions(Restrictions::default($this->restrictions, Restrictions::new(DIRECTORY_DATA . 'mplayer', true, 'audio')))
             ->setCommand('mplayer')
             ->addArgument($this->file)
             ->execute($background ? EnumExecuteMethod::background : EnumExecuteMethod::noReturn);
    }
}
