<?php

namespace Phoundation\Processes;

use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\Command;
use Phoundation\Processes\Exception\ProcessException;
use Phoundation\Processes\Exception\ProcessFailedException;
use Phoundation\Servers\Server;


/**
 * Class Process
 *
 * This class embodies a process that will be executed
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 * @uses \Phoundation\Processes\ProcessVariables
 */
Class Process
{
    use ProcessVariables;



    /**
     * Create a new process factory
     *
     * @param string|null $command
     * @param Restrictions|array|string|null $restrictions
     * @param string|null $packages
     * @return static
     */
    public static function new(?string $command = null, Restrictions|array|string|null $restrictions = null, ?string $packages = null): static
    {
        return new static($command, $restrictions, $packages);
    }



    /**
     * Create a new CLI script process factory
     *
     * @param string|null $command
     * @param Restrictions|array|string|null $restrictions
     * @param string|null $packages
     * @return static
     */
    public static function newCliScript(?string $command = null, Restrictions|array|string|null $restrictions = null, ?string $packages = null): static
    {
        $process = static::new('cli', $restrictions, $packages);
        $process->addArguments(Arrays::force($command, ' '));

        return $process;
    }



    /**
     * Processes constructor.
     *
     * @param string|null $command
     * @param Restrictions|array|string|null $restrictions
     * @param string|null $packages
     */
    public function __construct(?string $command = null, Restrictions|array|string|null $restrictions = null, ?string $packages = null)
    {
        // Ensure that the run files directory is available
        Path::new(PATH_ROOT . 'data/run/', $restrictions)->ensure();

        $this->setRestrictions($restrictions);

        if ($packages) {
            $this->setPackages($packages);
        }

        if ($command) {
            $this->setCommand($command);
        }
    }



    /**
     * Sets the server on which this command should be executed
     *
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }



    /**
     * Sets the server on which this command should be executed
     *
     * @param Server|string $server
     * @return $this
     */
    public function setServer(Server|string $server): static
    {
        $this->server = Server::get($server);
        return $this;
    }



    /**
     * Sets the actual CLI exit code after the process finished its execution
     *
     * This method will check if the specified exit code is accepted and if not, throw a Process exception
     *
     * @param int $exit_code
     * @param string|array|null $output
     * @return int
     */
    protected function setExitCode(int $exit_code, string|array|null $output = null): int
    {
        $this->exit_code = $exit_code;

        if (!in_array($exit_code, $this->accepted_exit_codes)) {
            // The command finished with an error
            throw new ProcessFailedException(tr('The command ":command" failed with exit code ":code"', [
                ':command' => $this->command,
                ':code'    => $exit_code
            ]), [
                'command'      => $this->command,
                'full_command' => $this->getFullCommandLine(),
                'pipe'         => $this->pipe?->getFullCommandLine(),
                'arguments'    => $this->arguments,
                'timeout'      => $this->timeout,
                'pid'          => $this->pid,
                'term'         => $this->term,
                'sudo'         => $this->sudo,
                'log_file'     => $this->log_file,
                'run_file'     => $this->run_file,
                'exit_code'    => $exit_code,
                'output'       => $output,
            ], $exit_code);
        }

        // All okay, yay!
        return $exit_code;
    }



    /**
     * Execute the command using the PHP exec() call and return an array
     *
     * @return array The output from the executed command
     */
    public function executeReturnArray(): array
    {
        if ($this->debug) {
            Log::printr($this->getFullCommandLine());
        } else {
            Log::action(tr('Executing command ":command" using exec() to return an array', [
                ':command' => $this->getFullCommandLine()
            ]), 2);
        }

        exec($this->getFullCommandLine(), $output, $exit_code);
        $this->setExitCode($exit_code, $output);
        return $output;
    }



    /**
     * Execute the command using the PHP exec() call and return a string
     *
     * @return string The output from the executed command
     */
    public function executeReturnString(): string
    {
        $output = $this->executeReturnArray();
        $output = implode(PHP_EOL, $output);

        return $output;
    }



    /**
     * Execute the command using the PHP exec() call and return a string
     *
     * @return void
     */
    public function executeNoReturn(): void
    {
        $this->executeReturnArray();
    }



    /**
     * Execute the command using passthru and send the output directly to the client
     *
     * @return bool
     */
    public function executePassthru(): bool
    {
        $output_file = Filesystem::createTempFile(false)->getFile();

        $commands = $this->getFullCommandLine();
        $commands = Strings::endsNotWith($commands, ';');

        if ($this->debug) {
            Log::printr($this->getFullCommandLine());
        } elseif (Debug::enabled()) {
            Log::action(tr('Executing command ":commands" using passthru()', [':commands' => $commands]), 2);
        }


        $result = passthru($this->getFullCommandLine(), $exit_code);

        // Output available in output file?
        if (file_exists($output_file)) {
            $output = file($output_file);
            unlink($output_file);
        } else {
            $output = null;
        }

        $this->setExitCode($exit_code, $output);

        // So according to the documentation, for some reason passthru() would return null on success and false on
        // failure. Makes sense, right? Just return true or false, please,
        if ($result === false) {
            return false;
        }

        return true;
    }



    /**
     * Executes the command for this object as a background process
     *
     * @return int The PID (Process ID) of the process running in the background
     */
    public function executeBackground(): int
    {
        // Ensure that this background command uses a terminal,
        $this->setTerm('xterm', true);

        if ($this->debug) {
            Log::printr($this->getFullCommandLine());
        } else {
            Log::notice(tr('Executing background command ":command" using exec()', [':command' => $this->getFullCommandLine(true)]), 3);
        }

        exec($this->getFullCommandLine(true), $output, $exit_code);

        if ($exit_code) {
            // Something went wrong immediately while executing the command?
            throw new ProcessException(tr('Failed to start process ":command" (Full command ":fullcommand") in background. It caused exit code ":code" with output ":output"', [':command' => $this->getCommand(), ':fullcommand' => $command, ':code' => $exit_code, ':output' => $output]));
        }

        // Set the process id and exit code for the nohup command
        $this->setPid();
        $exit_code = $this->setExitCode($exit_code, $output);
        Log::success(tr('Executed background command ":command" with PID ":pid"', [':command' => $this->real_command, ':pid' => $this->pid]), 4);

        return $this->pid;
    }



    /**
     * Returns if the process has executed or not
     *
     * @return bool
     */
    public function hasExecuted(): bool
    {
        return !($this->exit_code === null);
    }



    /**
     * Returns if the process is currently executing
     *
     * @return bool
     */
    public function isExecuting(): bool
    {
        return !($this->pid === null);
    }


    /**
     * Kill this (backgroun) process
     *
     * @param int $signal
     * @return void
     */
    public function kill(int $signal = 15): void
    {
        if ($this->pid) {
            Command::new($this->restrictions)->killPid($signal, $this->pid);
        }
    }



    /**
     * Builds and returns the command line that will be executed
     *
     * @param bool $background
     * @return string
     */
    public function getFullCommandLine(bool $background = false): string
    {
        $this->failed = false;

        if ($this->cached_command_line) {
            return $this->cached_command_line;
        }

        if ($this->register_run_file) {
            // Make sure we have a Pid file
            $this->setRunFile();
        }

        if (!$this->command) {
            throw new ProcessException(tr('Cannot execute process, no command specified'));
        }

        // Update the arguments with the variables
        foreach ($this->arguments as &$argument) {
            if (preg_match('/^\$.+?\$$/', $argument)) {
                if (!array_key_exists($argument, $this->variables)) {
                    // This variable was not defined, cannot apply it.
                    throw new ProcessException(tr('Specified variable ":variable" in the argument list was not defined', [':variable' => $argument]));
                }

                // Update and escape the argument
                $argument = escapeshellarg($this->variables[$argument]);
            }
        }

        unset($value);

        // Add arguments to the command
        $this->cached_command_line = $this->command . ' ' . implode(' ', $this->arguments);

        // Add wait
        if ($this->wait) {
            $this->cached_command_line = 'sleep ' . $this->wait . '; ' . $this->cached_command_line;
        }

        // Add timeout
        if ($this->timeout) {
            $this->cached_command_line = 'timeout --foreground ' . escapeshellarg($this->timeout) . ' ' . $this->cached_command_line;
        }

        // Execute the command in this directory
        if ($this->execution_path) {
            $this->cached_command_line = 'cd ' . escapeshellarg($this->execution_path) . '; ' . $this->cached_command_line;
        }

        // Add sudo
        if ($this->sudo) {
            $this->cached_command_line = 'sudo -u ' . escapeshellarg($this->sudo) . ' ' . $this->cached_command_line;
        }

        // Execute the command in the specified terminal
        if ($this->term) {
            $this->cached_command_line = 'export TERM=' . $this->term . '; ' . $this->cached_command_line;
        }

        // Pipe the output through to the next command
        if ($this->pipe) {
            $this->cached_command_line .= ' | ' . $this->pipe->getFullCommandLine();
        }

        // Redirect command output to the specified files for the specified channels
        foreach ($this->output_redirect as $channel => $file) {
            switch ($file[0]) {
                case '&':
                    // Redirect to different channel
                    $redirect = ' ' . $channel . '>&' . $file[1] . ' ';
                    break;

                case '*':
                    // Redirect to file and append
                    $file = substr($file, 1);;
                    $redirect = ' ' . $channel . '>> ' . $file;
                    break;

                default:
                    // Redirect to file and overwrite
                    $redirect = ' ' . $channel . '> ' . $file;
            }

            $this->cached_command_line .= $redirect;
        }

        // Execute on a server?
        if (isset($this->server)) {
            $this->cached_command_line = $this->server->getSshCommandLine($this->cached_command_line);
        }

        // Background commands get some extra options around
        if ($background) {
            $this->cached_command_line = '(nohup bash -c "set -o pipefail; ' . $this->cached_command_line . ' ; echo $$" > ' . $this->log_file . ' 2>&1 & echo $! >&3) 3> ' . $this->run_file;
        } elseif ($this->register_run_file) {
            // Make sure the PID will be registered in the run file
            $this->cached_command_line = 'bash -c "set -o pipefail; ' . $this->cached_command_line . '"; echo $$ > ' . $this->run_file;
        }

        return $this->cached_command_line;
    }
}