<?php

/**
 * Class PhoCommand
 *
 * This class is used to easily execute Phoundation commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Processes\Commands\Interfaces\PhoCommandInterface;
use Phoundation\Os\Processes\WorkersCore;
use Phoundation\Utils\Arrays;


class PhoCommand extends WorkersCore implements PhoCommandInterface
{
    /**
     * PhoCommand class constructor.
     *
     * @param array|string|null $commands
     * @param bool              $which_command
     */
    public function __construct(array|string|null $commands, bool $which_command = true)
    {
        if (is_string($commands)) {
            $commands = str_replace('/', ' ', $commands);
        }

        // Ensure that the run files directory is available
        FsDirectory::new(DIRECTORY_SYSTEM . 'run/', FsRestrictions::new(DIRECTORY_SYSTEM . 'run'))
                 ->ensure();

        parent::__construct(FsRestrictions::new(DIRECTORY_ROOT . 'pho'));

        $this->setCommand(DIRECTORY_ROOT . 'pho', $which_command)
             ->addArguments(['-E', ENVIRONMENT])
             ->addArguments($commands ? Arrays::force($commands, ' ') : null);
    }


    /**
     * Create a new process factory for a specific Phoundation command
     *
     * @param array|string|null $commands
     * @param bool              $which_command
     *
     * @return static
     */
    public static function new(array|string|null $commands, bool $which_command = true): static
    {
        return new static($commands, $which_command);
    }
}
