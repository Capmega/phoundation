<?php

/**
 * Class Command
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Processes\Commands\Exception\CommandNotFoundException;
use Phoundation\Os\Processes\Commands\Exception\NoSudoException;
use Phoundation\Os\Processes\Commands\Interfaces\CommandInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;
use Phoundation\Os\Processes\ProcessCore;
use Phoundation\Utils\Arrays;
use Stringable;


abstract class Command extends ProcessCore implements CommandInterface
{
    /**
     * Command constructor.
     *
     * @param FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory
     * @param Stringable|string|null                            $operating_system
     * @param string|null                                       $packages
     */
    public function __construct(FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory = null, Stringable|string|null $operating_system = null, ?string $packages = null)
    {
        parent::__construct($execution_directory);

        // Ensure that the run files directory is available
        FsDirectory::new(
            DIRECTORY_SYSTEM . 'run/',
            FsRestrictions::new(DIRECTORY_SYSTEM . 'run', true)
        )->ensure();

        if ($operating_system or $packages) {
            $this->setPackages($operating_system, $packages);
        }
    }


    /**
     * Create a new process factory for a specific command
     *
     * @param FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory
     * @param string|null                                       $operating_system
     * @param string|null                                       $packages
     *
     * @return static
     */
    public static function new(FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory = null, ?string $operating_system = null, ?string $packages = null): static
    {
        return new static($execution_directory, $operating_system, $packages);
    }


    /**
     * Returns true if the specified commands can be executed with sudo privileges
     *
     * @param array|string   $commands
     * @param FsRestrictions $restrictions
     * @param bool           $exception
     *
     * @return bool
     * @todo Find a better option than "--version" which may not be available for everything. What about shell commands
     *       like "true", or "which", etc?
     */
    public static function checkSudoAvailable(array|string $commands, FsRestrictions $restrictions, bool $exception = false): bool
    {
        try {
            $command = null;

            foreach (Arrays::force($commands) as $command) {
                Process::new($command, $restrictions)
                       ->setSudo(true)
                       ->addArgument('--version')
                       ->executeReturnArray();
            }

            return true;

        } catch (CommandNotFoundException) {
            if ($exception) {
                throw new NoSudoException(tr('Cannot check for sudo privileges for the ":command" command, the command was not found', [
                    ':command' => $command,
                ]));
            }

        } catch (ProcessFailedException) {
            if ($exception) {
                throw new NoSudoException(tr('The current process owner has no sudo privileges available for the ":command" command', [
                    ':command' => $command,
                ]));
            }
        }

        return false;
    }
}
