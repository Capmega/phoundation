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

use Phoundation\Data\Traits\TraitDataEnvironment;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
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
     * PhoCommand class constructor.
     *
     * @param string|null $command
     */
    public function __construct(?string $command = null)
    {
        if (!$command) {
            $command = DIRECTORY_ROOT . 'pho';
        }

        // Ensure that the run files directory is available
        FsDirectory::new(DIRECTORY_SYSTEM . 'run/', FsRestrictions::new(DIRECTORY_SYSTEM . 'run'))
            ->ensure();

        // Generate the process
        parent::__construct(FsRestrictions::new($command));

        // Set the command to execute phoundation, pass basic arguments and settings like timeout
        // No-sound is always set to avoid sub commands pinging differently from the main command, causing confusion
        $this->setCommand($command, false)
            ->addArguments(['-N'])
            ->setEnvironment(ENVIRONMENT)
            ->setTimeout($this->timeout);
    }


    /**
     * Create a new process factory for a specific Phoundation command
     *
     * @param string|null $pho_command
     *
     * @return static
     */
    public static function new(?string $pho_command = null): static
    {
        return new static($pho_command);
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
        $this->pho_commands = Arrays::force($pho_commands, ' ');

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
        if (empty($this->pho_commands)) {
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
}
