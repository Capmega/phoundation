<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Arrays;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Exception\CommandNotFoundException;
use Phoundation\Os\Processes\Commands\Exception\NoSudoException;
use Phoundation\Os\Processes\Commands\Interfaces\CommandInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;
use Phoundation\Os\Processes\ProcessCore;


/**
 * Class Command
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
abstract class Command extends ProcessCore implements CommandInterface
{
    /**
     * Command constructor.
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null $packages
     */
    public function __construct(RestrictionsInterface|array|string|null $restrictions = null, ?string $packages = null)
    {
        parent::__construct($restrictions);

        // Ensure that the run files directory is available
        Directory::new(DIRECTORY_ROOT . 'data/run/', Restrictions::new(DIRECTORY_DATA . 'run', true))->ensure();

        $this->setRestrictions($restrictions);

        if ($packages) {
            $this->setPackages($packages);
        }
    }


    /**
     * Create a new process factory for a specific command
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null $packages
     * @return static
     */
    public static function new(RestrictionsInterface|array|string|null $restrictions = null, ?string $packages = null): static
    {
        return new static($restrictions, $packages);
    }


    /**
     * Returns true if the specified commands can be executed with sudo privileges
     *
     * @param array|string $commands
     * @param Restrictions $restrictions
     * @param bool $exception
     * @return bool
     * @todo Find a better option than "--version" which may not be available for everything. What about shell commands like "true", or "which", etc?
     */
    public static function sudoAvailable(array|string $commands, Restrictions $restrictions, bool $exception = false): bool
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
                    ':command' => $command
                ]));
            }

        } catch (ProcessFailedException) {
            if ($exception) {
                throw new NoSudoException(tr('The current process owner has no sudo privileges available for the ":command" command', [
                    ':command' => $command
                ]));
            }
        }

        return false;
    }
}
