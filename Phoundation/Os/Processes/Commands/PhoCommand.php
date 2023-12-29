<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Interfaces\PhoCommandCoreInterface;
use Phoundation\Os\Processes\WorkersCore;
use Phoundation\Utils\Arrays;


/**
 * Class PhoCommand
 *
 * This class is used to easily execute Phoundation commands
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class PhoCommand extends WorkersCore implements PhoCommandCoreInterface
{
    /**
     * PhoCommand class constructor.
     *
     * @param array|string|null $commands
     */
    public function __construct(array|string|null $commands)
    {
        // Ensure that the run files directory is available
        Directory::new(DIRECTORY_ROOT . 'data/run/', Restrictions::new(DIRECTORY_DATA . 'run', true))->ensure();

        parent::__construct(Restrictions::new(DIRECTORY_ROOT . 'pho'));

        $this->setCommand(DIRECTORY_ROOT . 'pho')
            ->addArguments(['-E', ENVIRONMENT])
            ->addArguments($commands ? Arrays::force($commands) : null);
    }


    /**
     * Create a new process factory for a specific Phoundation command
     *
     * @param array|string|null $commands
     * @return static
     */
    public static function new(array|string|null $commands): static
    {
        return new static($commands);
    }
}
