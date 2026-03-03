<?php

/**
 * Class Mpg123
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\TraitDataFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;


class Mpg123 extends Command
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
             ->setRestrictionsObject(PhoRestrictions::getRestrictionsOrDefaultObject($this->_restrictions, PhoRestrictions::new(DIRECTORY_DATA . 'mpg123', true, 'audio')))
             ->setCommand('mpg123')
             ->appendArgument($this->_file)
             ->execute($background ? EnumExecuteMethod::background : EnumExecuteMethod::noReturn);
    }
}
