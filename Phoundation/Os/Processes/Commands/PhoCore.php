<?php

/**
 * Class PhoCommand
 *
 * This class is used to easily execute Phoundation commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataEnvironment;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Processes\Commands\Interfaces\PhoInterface;
use Phoundation\Os\Processes\Exception\ProcessException;
use Phoundation\Os\Workers\WorkersCore;
use Phoundation\Utils\Arrays;

class PhoCore extends WorkersCore implements PhoInterface
{
    use TraitDataEnvironment;


    /**
     * The Phoundation commands
     *
     * @var array|null $pho_commands
     */
    protected ?array $pho_commands;

    /**
     * Tracks if environment and pho sub commands have been added to the command line
     *
     * @var bool $added_environment_commands
     */
    protected bool $added_environment_commands = false;


    /**
     * Initializes the PhoCore class.
     *
     * @param array|string|null     $commands
     * @param PhoFileInterface|null $pho
     */
    protected function init(array|string|null $commands, ?PhoFileInterface $pho = null)
    {
        if (is_string($commands)) {
            $commands = str_replace('/', ' ', $commands);
        }

        if (!$pho) {
            $pho = PhoFile::new(DIRECTORY_ROOT . 'pho', PhoRestrictions::newRootObject());
        }

        // Ensure that the run files directory is available
        PhoDirectory::new(DIRECTORY_SYSTEM . 'run/', PhoRestrictions::newWritable(DIRECTORY_SYSTEM . 'run'))
                   ->ensure();

        // Generate the process
        parent::__construct($pho->getParentDirectoryObject());

        // Set the command to execute phoundation, pass basic arguments and settings like timeout
        // --no-audio        is always added to avoid sub commands pinging differently from the main command, causing confusion
        // --ignore-readonly is always added if the current process is also ignoring the readonly mode file
        // --no-warnings     is always added because all pho commands should NEVER cause warning type exceptions.
        $this->setExecutionDirectory(PhoDirectory::newRoot())
             ->setCommand($pho->getSource(), false)
             ->appendArguments(['--no-audio', '--no-warnings'])
             ->appendArguments(Core::getIgnoreReadonly() || Core::inInitState() ? '--ignore-readonly' : null)
             ->setPhoCommands($commands)
             ->setEnvironment(ENVIRONMENT)
             ->setTimeout($this->timeout);
    }


    /**
     * Returns the Phoundation commands
     *
     * @return array|null
     */
    public function getPhoCommands(): ?array
    {
        return $this->pho_commands;
    }


    /**
     * Sets the Phoundation commands
     *
     * @param array|string|null $pho_commands
     * @return static
     */
    public function setPhoCommands(array|string|null $pho_commands): static
    {
        if (is_string($pho_commands)) {
            $pho_commands = str_replace('/', '', $pho_commands);
            $pho_commands = Arrays::force($pho_commands, ' ');
        }

        $this->pho_commands = $pho_commands;

        return $this;
    }


    /**
     * Returns the full command line if a PHO command has been specified
     *
     * @param bool $background
     * @param bool $pipe
     *
     * @return string
     */
    public function getFullCommandLine(bool $background = false, bool $pipe = false): string
    {
        if (empty($this->pho_commands) and empty($this->arguments)) {
            // TODO Add exceptions for commands like "pho -Z"
            throw new ProcessException(tr('Cannot execute PHO command, no command specified'));
        }

        if (!$this->environment) {
            throw new ProcessException(tr('Cannot execute PHO command, no environment specified'));
        }

        // Add the Phoundation commands to the arguments
        if (empty($this->cached_command_line)) {
            if (!$this->added_environment_commands) {
                $this->prependArguments($this->pho_commands);
                $this->prependArguments(['-E', $this->environment]);

                $this->added_environment_commands = true;
            }
        }

        return parent::getFullCommandLine($background);
    }


    /**
     * Executes this Pho command as a background process
     *
     * @return int
     */
    public function executeBackground(): int
    {
        $pid = parent::executeBackground();

        Log::action(ts('Executing background Pho command ":command" with PID ":pid"', [
            ':command' => implode(' ', $this->pho_commands),
            ':pid'     => $pid
        ]));

        return $pid;
    }


    /**
     * Executes this Pho command in a normal way, returning the output as an array
     *
     * @return array
     */
    public function executeReturnArray(): array
    {
        Log::action(ts('Executing normal Pho command ":command"', [
            ':command' => implode(' ', $this->pho_commands)
        ]));

        return parent::executeReturnArray();
    }


    /**
     * Executes this Pho command, passing through all output directly to the client
     *
     * @return bool
     */
    public function executePassthru(): bool
    {
        Log::action(ts('Executing passthru Pho command ":command"', [
            ':command' => implode(' ', $this->pho_commands)
        ]));

        return parent::executePassthru();
    }
}
