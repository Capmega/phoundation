<?php

namespace Phoundation\Processes;

use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Exception\ProcessesException;

/**
 * Class Process
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
Class Process
{
    /**
     * The command that will be executed for this process
     *
     * @var string|null $command
     */
    protected ?string $command = null;

    /**
     * The log file where command output will be written to
     *
     * @var string|null
     */
    protected ?string $log_file = null;

    /**
     * The run path where command output will be written to
     *
     * @var string|null $run_path
     */
    protected ?string $run_path = null;



    /**
     * Processes constructor.
     */
    public function __construct()
    {
    }



    /**
     * Set the command to be executed for this process
     *
     * @param string $command
     * @return Process
     */
    public function setCommand(string $command): Process
    {
        $command = trim($command);

        if (!$command) {
            throw new OutOfBoundsException(tr('No command specified'));
        }

        // Check if the command exist on disk
        if (!file_exists($command)) {
//            $real_command = Commands::which($command);
//            $real_command = $command;
            $real_command = null;

            if (!$real_command) {
                throw new ProcessesException(tr('Specified process command ":command" does not exist', ['command' => $command]));
            }

            $command = $real_command;
        }

        // Apply proper escaping and register the command
        $this->command = escapeshellcmd($command);
    }



    /**
     * Returns the command to be executed for this process
     *
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }



    /**
     * Sets the log file where the process output will be redirected to
     *
     * @return string
     */
    public function getLogFile(): string
    {
        return $this->log_file;
    }



    /**
     * Returns the log file where the process output will be redirected to
     *
     * @param string $file
     * @return void
     */
    public function setLogFile(string $file): void
    {
        $this->log_file = $file;
    }



    /**
     * Set the run path where the process run file will be written
     *
     * @return string
     */
    public function geRunPath(): string
    {
        return $this->run_path;
    }



    /**
     * Returns the run path where the process run file will be written
     *
     * @param string $path
     * @return void
     */
    public function setRunPath(string $path): void
    {
        $this->run_path = $path;
    }







}