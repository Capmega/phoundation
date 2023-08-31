<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;


/**
 * Class AptGet
 *
 * This class manages the apt-get command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class AptGet extends Command
{
    /**
     * Install the specified packages
     *
     * @param array|string $packages
     * @return void
     */
    public function install(array|string $packages): void
    {
        Log::action(tr('Installing packages ":packages"', [':packages' => $packages]));

        $this->setInternalCommand('apt-get')
             ->setSudo(true)
             ->addArguments(['-y', 'install'])
             ->addArguments($packages)
             ->setTimeout(120)
             ->executePassthru();
    }
}
