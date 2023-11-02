<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Interfaces\PhoTaskInterface;
use Phoundation\Os\Processes\Task;


/**
 * Class PhoTask
 *
 * This class is used to easily execute Phoundation commands as background tasks
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class PhoTask extends Task implements PhoTaskInterface
{
    /**
     * PhoTask class constructor.
     *
     * @param string $command
     */
    public function __construct(string $command)
    {
        // Ensure that the run files directory is available
        Directory::new(PATH_ROOT . 'data/run/', Restrictions::new(PATH_DATA . 'run', true))->ensure();

        parent::__construct(Restrictions::new(PATH_ROOT . '/pho'));

        $this->setInternalCommand(PATH_ROOT . '/pho')
             ->addArgument($command);
    }


    /**
     * Create a new process factory for a specific Phoundation command
     *
     * @param string $command
     * @return static
     */
    public static function new(string $command): static
    {
        return new static($command);
    }
}
