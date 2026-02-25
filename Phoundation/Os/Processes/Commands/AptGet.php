<?php

/**
 * Class AptGet
 *
 * This class manages the apt-get command
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;


class AptGet extends Command
{
    /**
     * Installs the specified packages on the system
     *
     * @param array|string $packages
     *
     * @return void
     */
    public function install(array|string $packages): static
    {
        $this->setCommand('apt-get')
             ->setSudo(true)
             ->appendArguments([
                 '-y',
                 'install',
             ])
             ->appendArguments($packages)
             ->setTimeout(120)
             ->executePassthru();
    }


    /**
     * Removes the specified packages from the system
     *
     * @param array|string $packages
     * @param bool         $purge
     *
     * @return void
     */
    public function remove(array|string $packages, bool $purge = false): static
    {
        Log::action(ts('Removing packages ":packages"', [':packages' => $packages]));
        $this->setCommand('apt')
             ->setSudo(true)
             ->appendArguments([
                 'remove',
                 '-y',
             ])
             ->appendArgument($purge ? '--purge' : null)
             ->appendArguments($packages)
             ->setTimeout(120)
             ->executePassthru();
    }
}
