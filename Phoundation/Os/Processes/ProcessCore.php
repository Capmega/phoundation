<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Commands\Kill;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Os\Processes\Exception\ProcessException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Interfaces\ProcessCoreInterface;
use Phoundation\Os\Processes\Interfaces\ProcessVariablesInterface;
use Phoundation\Servers\Server;


/**
 * Class ProcessCore
 *
 * This class embodies a process that will be executed
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 * @uses ProcessVariables
 */
abstract class ProcessCore implements  ProcessVariablesInterface, ProcessCoreInterface
{
    use ProcessVariables;


    /**
     * Create a new CLI script process factory
     *
     * @param string|null $command
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null $packages
     * @return static
     */
    public static function newCliScript(?string $command = null, RestrictionsInterface|array|string|null $restrictions = null, ?string $packages = null): static
    {
        $process = static::new('cli', $restrictions, $packages);
        $process->addArguments(Arrays::force($command, ' '));

        return $process;
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
        $this->stop      = microtime(true);
        $this->exit_code = $exit_code;

        if (!in_array($exit_code, $this->accepted_exit_codes)) {
            switch ($exit_code) {
                case 124:
                    $cause = 'timeout';
                    break;

                default:
                    $cause = 'unknown';
            }

            // The command finished with an error
            throw ProcessFailedException::new(tr('The command ":command" failed with exit code ":code"', [
                ':command' => $this->command,
                ':code'    => $exit_code
            ]))->setCode($exit_code)->addData([
                'command'              => $this->command,
                'full_command'         => $this->getFullCommandLine(),
                'pipe'                 => $this->getPipeCommandLine(),
                'arguments'            => $this->arguments,
                'variables'            => $this->variables,
                'timeout'              => $this->timeout,
                'pid'                  => $this->pid,
                'term'                 => $this->term,
                'sudo'                 => $this->sudo,
                'log_file'             => $this->log_file,
                'run_file'             => $this->run_file,
                'exit_code'            => $exit_code,
                'output'               => $output,
                'probable_cause'       => $cause,
                'execution_time'       => $this->getExecutionTime(),
                'execution_stop_time'  => $this->getExecutionStopTime(),
                'execution_start_time' => $this->getExecutionStartTime(),
            ]);
        }

        // All okay, yay!
        return $exit_code;
    }


    /**
     * Execute the command using the PHP exec() call and return an array
     *
     * @return IteratorInterface The output from the executed command
     */
    public function executeReturnIterator(): IteratorInterface
    {
        return Iterator::new()->setSource($this->executeReturnArray());
    }


    /**
     * Execute the command using the PHP exec() call and return an array
     *
     * @return array The output from the executed command
     */
    public function executeReturnArray(): array
    {
        if ($this->debug) {
            Log::printr(Strings::untilReverse($this->getFullCommandLine(), 'exit '));

        } else {
            Log::action(tr('Executing command ":command" using exec() to return an array', [
                ':command' => $this->getFullCommandLine()
            ]), 2);
        }

        $this->start = microtime(true);
        exec($this->getFullCommandLine(), $output, $exit_code);
        $this->setExitCode($exit_code, $output);

        if ($this->debug) {
            Log::notice($output, 3);
        }

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
     * Execute the command and depending on specified method, return or log output
     *
     * @param EnumExecuteMethodInterface $method
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function execute(EnumExecuteMethodInterface $method): IteratorInterface|array|string|int|bool|null
    {
        switch ($method) {
            case EnumExecuteMethod::log:
                $results = $this->executeReturnArray();
                Log::notice($results, 4);
                return null;

            case EnumExecuteMethod::background:
                return $this->executeBackground();

            case EnumExecuteMethod::passthru:
                return $this->executePassthru();

            case EnumExecuteMethod::returnString:
                return $this->executeReturnString();

            case EnumExecuteMethod::returnArray:
                return $this->executeReturnArray();

            case EnumExecuteMethod::returnIterator:
                return $this->executeReturnIterator();

            case EnumExecuteMethod::noReturn:
                $this->executeNoReturn();
                return null;

            default:
                throw new OutOfBoundsException(tr('Unknown execute method ":method" specified', [
                    ':method' => $method
                ]));
        }
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
            Log::printr(Strings::untilReverse($this->getFullCommandLine(), 'exit '));
        } elseif (Debug::getEnabled()) {
            Log::action(tr('Executing command ":commands" using passthru()', [':commands' => $commands]), 2);
        }

        $this->start = microtime(true);
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
            Log::notice(tr('Executing background command ":command" using exec()', [
                ':command' => $this->getFullCommandLine(true)
            ]), 3);
        }

        $this->start = microtime(true);
        exec($this->getFullCommandLine(true), $output, $exit_code);

        if ($exit_code) {
            // Something went wrong immediately while executing the command?
            throw new ProcessException(tr('Failed to start process ":command" (Full command ":full_command") in background. It caused exit code ":code" with output ":output"', [
                ':command'      => $this->getCommand(),
                ':full_command' => $this->getFullCommandLine(true),
                ':code'         => $exit_code,
                ':output'       => $output
            ]));
        }

        // Set the process id and exit code for the nohup command
        $this->setPid();
        $exit_code = $this->setExitCode(0, $output);

        Log::success(tr('Executed background command ":command" with PID ":pid"', [
            ':command' => $this->real_command,
            ':pid'     => $this->pid
        ]), 4);

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
            Kill::new($this->restrictions)->pid($signal, $this->pid);
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
        $arguments = [];
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
        foreach ($this->arguments as $argument) {
            // Does this argument contain variables? If so, apply them here
            if (preg_match('/^\$.+?\$$/', $argument)) {
                if (!array_key_exists($argument, $this->variables)) {
                    // This variable was not defined, cannot apply it.
                    throw new ProcessException(tr('Specified variable ":variable" in the argument list was not defined', [
                        ':variable' => $argument
                    ]));
                }

                // Update and escape the argument
                $argument = escapeshellarg($this->variables[$argument]);
            }

            // Escape quotes if required so for shell
            for ($i = 0; $i < $this->escape_quotes; $i++) {
                $argument = str_replace('\\', '\\\\', $argument);
                $argument = str_replace('\'', '\\\'', $argument);
            }

            $arguments[] = $argument;
        }

        // Add arguments to the command
        $this->cached_command_line = $this->real_command . ' ' . implode(' ', $arguments);

//        // Add sudo
//        if ($this->sudo) {
//            $this->cached_command_line = $this->sudo . ' ' . $this->cached_command_line;
//        }

        // Add timeout
        if ($this->timeout) {
            $this->cached_command_line = 'timeout --foreground ' . escapeshellarg((string)$this->timeout) . ' ' . $this->cached_command_line;
        }

        // Add wait
        if ($this->wait) {
            $this->cached_command_line = 'sleep ' . ($this->wait / 1000) . '; ' . $this->cached_command_line;
        }

        // Execute the command in this directory
        if ($this->execution_path) {
            $this->cached_command_line = 'cd ' . escapeshellarg($this->execution_path) . '; ' . $this->cached_command_line;
        }

        // Execute on a server?
        if (isset($this->server)) {
            $this->cached_command_line = $this->server->getSshCommandLine($this->cached_command_line);
        }

        // Execute the command in the specified terminal
        if ($this->term) {
            $this->cached_command_line = 'export TERM=' . $this->term . '; ' . $this->cached_command_line;
        }

        // Pipe the output through to the next command
        if ($this->pipe) {
            $this->cached_command_line .= ' | ' . $this->getPipeCommandLine();
        }

        // Redirect command output to the specified files for the specified channels
        foreach ($this->output_redirect as $channel => $file) {
            switch (substr($file, 0, 2)) {
                case '>&':
                    if ((strlen($file) !== 3) or !is_numeric($file[2])) {
                        throw new ProcessException(tr('Invalid output redirect ":redirect" specified', [
                            ':redirect' => $file
                        ]));
                    }

                    // Redirect to different channel
                    $redirect = ' ' . $channel . '>&' . $file[2] . ' ';
                    break;

                case '>>':
                    // Redirect to file and append
                    $file = substr($file, 2);
                    $redirect = ' ' . $channel . '>> ' . $file;
                    break;

                default:
                    // Redirect to file and overwrite
                    $redirect = ' ' . $channel . '> ' . $file;
            }

            $this->cached_command_line .= $redirect;
        }

        // Redirect command input from the specified files for the specified channels
        foreach ($this->input_redirect as $channel => $file) {
            $this->cached_command_line .= ' ' . (($channel > 1) ? $channel : '') . '< ' . $file;
        }

        // Background commands get some extra options around
        if ($this->use_run_file) {
            // Create command line with run file
            if ($background) {
                $this->cached_command_line = "(nohup bash -c 'set -o pipefail; " . str_replace("'", '"', $this->cached_command_line) . " ; EXIT=\$?; echo \$\$; exit \$EXIT' > " . $this->log_file . " 2>&1 & echo \$! >&3) 3> " . $this->run_file;

            } elseif ($this->register_run_file) {
                // Make sure the PID will be registered in the run file
                $this->cached_command_line = "bash -c 'set -o pipefail; " . str_replace("'", '"', $this->cached_command_line) . "; exit \$?'; EXIT=\$?; echo \$\$ > " . $this->run_file . "; exit \$EXIT;";
            }
        } else {
            // Create command line without run file
            if ($background) {
                $this->cached_command_line = "(nohup bash -c 'set -o pipefail; " . str_replace("'", '"', $this->cached_command_line) . " ; EXIT=\$?; echo \$\$; exit \$EXIT' > " . $this->log_file . " 2>&1 & echo \$! >&3)";

            } elseif ($this->register_run_file) {
                // Make sure the PID will be registered in the run file
                $this->cached_command_line = "bash -c 'set -o pipefail; " . str_replace("'", '"', $this->cached_command_line) . "; exit \$?';";
            }
        }

        // Add sudo
        if ($this->sudo) {
            $this->cached_command_line = $this->sudo . ' ' . $this->cached_command_line;
        }

        return $this->cached_command_line;
    }


    /**
     * Command exception handler
     *
     * @param string $command
     * @param Exception $e
     * @param callable|null $function
     * @return void
     */
    protected static function handleException(string $command, Exception $e, ?callable $function = null): void
    {
        if ($e->getData()['output']) {
            $data = $e->getData()['output'];
            $first_line = Arrays::firstValue($data);
            $first_line = strtolower($first_line);
            $last_line = Arrays::lastValue($data);
            $last_line = strtolower($last_line);

            // Process specified handlers
            if ($function) {
                $function($first_line, $last_line, $e);
            }

            // Handlers were unable to make a clear exception out of this, show the standard command exception
            throw new CommandsException(tr('The command :command failed with ":output"', [
                ':command' => $command,
                ':output' => $data
            ]));
        }

        // The process generated no output. Process specified handlers
        if ($function) {
            $function(null, null, $e);
        }

        // Something else went wrong, no CLI output available
        throw new CommandsException(tr('The command :command failed for unknown reasons', [
            ':command' => $command
        ]));
    }
}
