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

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataEnvironment;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Os\Processes\Commands\Interfaces\PhoInterface;
use Phoundation\Os\Processes\Exception\ProcessException;
use Phoundation\Os\Processes\WorkersCore;
use Phoundation\Utils\Arrays;


class Pho extends WorkersCore implements PhoInterface
{
    use TraitDataEnvironment;


    /**
     * The Phoundation commands
     *
     * @var array|null $pho_commands
     */
    protected ?array $pho_commands;


    /**
     * Pho class constructor.
     *
     * @param array|string|null    $commands
     * @param FsFileInterface|null $pho
     */
    public function __construct(array|string|null $commands, ?FsFileInterface $pho = null)
    {
        if (is_string($commands)) {
            $commands = str_replace('/', ' ', $commands);
        }

        if (!$pho) {
            $pho = FsFile::new(DIRECTORY_ROOT . 'pho', FsRestrictions::newRoot());
        }

        // Ensure that the run files directory is available
        FsDirectory::new(DIRECTORY_SYSTEM . 'run/', FsRestrictions::new(DIRECTORY_SYSTEM . 'run'))
            ->ensure();

        // Generate the process
        parent::__construct($pho->getParentDirectory());

        // Set the command to execute phoundation, pass basic arguments and settings like timeout
        // --no-audio        is always added to avoid sub commands pinging differently from the main command, causing confusion
        // --ignore-readonly is always added if the current process is also ignoring the readonly mode file
        // --no-warnings     is always added because all pho commands should NEVER cause warning type exceptions.
        $this->setCommand($pho->getSource(), false)
             ->addArguments(['--no-audio', '--no-warnings'])
             ->addArguments(Core::getIgnoreReadonly() || Core::inInitState() ? '--ignore-readonly' : null)
             ->setPhoCommands($commands)
             ->setEnvironment(ENVIRONMENT)
             ->setTimeout($this->timeout);
    }


    /**
     * Create a new process factory for a specific Phoundation command
     *
     * @param string|null          $pho_command
     * @param FsFileInterface|null $pho
     *
     * @return static
     */
    public static function new(?string $pho_command = null, ?FsFileInterface $pho = null): static
    {
        return new static($pho_command, $pho);
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
     * @return string
     */
    public function getFullCommandLine(bool $background = false): string
    {
        if (empty($this->pho_commands) and empty($this->arguments)) {
            // TODO Add exceptions for commands like "pho -Z"
            throw new ProcessException(tr('Cannot execute PHO command, no command specified'));
        }

        if (!$this->environment) {
            throw new ProcessException(tr('Cannot execute PHO command, no environment specified'));
        }

        // Add the Phoundation commands to the arguments
        if (!$this->cached_command_line) {
            $this->prependArguments(['-E', $this->environment]);
            $this->prependArguments($this->pho_commands);
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

        Log::action(tr('Executing background Pho command ":command" with PID ":pid"', [
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
        Log::action(tr('Executing normal Pho command ":command"', [
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
        Log::action(tr('Executing passthru Pho command ":command"', [
            ':command' => implode(' ', $this->pho_commands)
        ]));

        return parent::executePassthru();
    }
}
