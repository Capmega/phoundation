<?php

/**
 * Class Dpkg
 *
 * This class manages the dpkg command
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;


class Dpkg extends Command
{
    /**
     * Installs the specified packages on the system
     *
     * @param array|string $packages
     *
     * @return static
     */
    public function getSelections(array|string $packages): static
    {
        $this->setCommand('dpkg')
             ->setSudo(true)
             ->appendArguments([
                 '--get-selections',
             ])
             ->appendArguments($packages)
             ->setTimeout(10)
             ->executePassthru();

        return $this;
    }
}
