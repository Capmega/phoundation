<?php

namespace Phoundation\Processes;

use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
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
     * Processes constructor.
     *
     * @param string|null $command
     * @param Server|null $server
     * @param bool $which_command
     */
    public function __construct(?string $command = null, ?Server $server = null, bool $which_command = false)
    {
        // Ensure that the run files directory is available
        Path::ensure(ROOT . 'data/run/');

        if ($server) {
            $this->setServer($server);
        }

        if ($command) {
            $this->setCommand($command, $which_command);
        }
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
            throw new ProcessFailedException(tr('The command ":command" failed with exit code ":code"', [':command' => $this->command, ':code' => $exit_code]), [
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
     * Execute the command using the PHP exec() cakk abd return an array
     *
     * @return array The output from the executed command
     */
    public function executeReturnArray(): array
    {
        Log::notice(tr('Executing command ":command" using exec() to return an array', [':command' => $this->getFullCommandLine()]));
        exec($this->getFullCommandLine(), $output, $exit_code);
        $this->setExitCode($exit_code, $output);
        return $output;
    }



    /**
     * Execute the command using passthru and send the output directly to the client
     *
     * @return bool
     */
    public function executePassthru(): bool
    {
        $exitcode_file = File::temp(false);
        $output_file   = File::temp(false);

        $exit_code = null;
        $output    = null;

        $commands = $this->getFullCommandLine();
        $commands = Strings::endsNotWith($commands, ';');

        if (Debug::enabled()) {
            Log::notice(tr('Executing command ":commands" using passthru()', [':commands' => $commands]));
        }

        $output = passthru($this->getFullCommandLine(), $exit_code);
        $this->setExitCode($exit_code);

        // Get output and exitcode from temp files
        // NOTE: In case of errors, these output files may NOT exist!
        if (file_exists($exitcode_file)) {
            $exit_code = trim(file_get_contents($exitcode_file));
        }

        if (file_exists($output_file)) {
            $output = file($output_file);
        }

        $this->setExitCode($exit_code, $output);

        // Remove the temp files
        FilesystemCommands::server($this->server)->delete($output_file);
        FilesystemCommands::server($this->server)->delete($exitcode_file);

        // So according to the documentation, for some reason passthru() would return null on success and false on
        // failure. Makes sense, right? Just return true or false, please,
        if ($output === false) {
            return false;
        }

        return true;
    }



    /**
     * Executes the command for this object as a background process
     *
     * @return int The PID (Process ID) of the process running in the background
     */
    public function executeBackground(bool $log = true): int
    {
        // Ensure that this background command uses a terminal,
        $this->setTerm('xterm', true);
        $this->setRegisterPid(false);

        $command = '(nohup bash -c "set -o pipefail; ' . $this->getFullCommandLine() . ' ; echo $$" > ' . $this->log_file . ' 2>&1 & echo $! >&3) 3> ' . $this->run_file;
        $this->setRegisterPid(true);

        Log::notice(tr('Executing background command ":command" using exec()', [':command' => $command]));
        exec($command, $output, $exit_code);

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
     * Executes the command for this object as a background service process running under PID 1
     *
     * @return int The PID (Process ID) of the process running in the background
     */
    public function executeService(): int
    {
//        Log::notice(tr('Executing background command ":command" using exec()', [':command' => $this->getFullCommandLine()]));
//
//        if ($log) {
//            if (substr($log, 0, 3) === 'tmp') {
//                /*
//                 * Log output to the TMP directory instead of the normal log output
//                 */
//                $log = TMP.Strings::startsNotWith(substr($log, 3), '/');
//
//            } else {
//                $log = ROOT.'data/log/'.$log;
//            }
//
//            file_ensure_path(dirname($log));
//
//            if (file_exists($log) and is_dir($log)) {
//                /*
//                 * Oops, the log file already exists, but as a directory! That is a
//                 * problem, get rid of it or it will stop background execution all
//                 * together!
//                 */
//                log_file(tr('Found specified log file ":path" to exist as a directory. Deleting the directory to avoid run_background() not working correctly', array(':path' => $log)), 'run_background');
//                file_delete($log, ROOT.'data/log');
//            }
//
//            $command = sprintf('export TERM='.$term.'; (nohup %s >> %s 2>&1 & echo $! >&3) 3> %s', 'sleep '.$wait.';'.$path.$cmd.' '.$args, $log, ROOT.'data/run-background/'.$cmd);
//            log_console(tr('Executing background command ":command"', array(':command' => $command)), 'VERBOSE/cyan');
//            exec($command);
//
//        } else {
//            $command = sprintf('export TERM='.$term.'; (nohup %s > /dev/null 2>&1 & echo $! >&3) 3> %s', 'sleep '.$wait.';'.$path.$cmd.' '.$args, ROOT.'data/run-background/'.$cmd);
//            log_console(tr('Executing background command ":command"', array(':command' => $command)), 'VERBOSE/cyan');
//            exec($command);
//        }
//
//
//
//
//
//
//
//
//
//
//
//
//
//        $command = 'nohup ' . $this->getFullCommandLine();
//        $command .= ' &';
//
//        exec($command, $output, $exit_code);
//
//        if ($exit_code) {
//            // Something went wrong immediately while executing the command?
//        }
//
//        $this->setExitCode($exit_code, $output);
//
//        // Get the process id
//        $pid = array_shift($output);
//
//        if (is_numeric($pid)) {
//            return $pid;
//        }
//
//        throw new ProcessException(tr('Failed to find the background process PID'));
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
            Commands::killPid($this->pid, $signal);
        }
    }



    /**
     * Builds and returns the command line that will be executed
     *
     * @return string
     * @throws ProcessException
     */
    public function getFullCommandLine(): string
    {
        if ($this->cached_command_line) {
            return $this->cached_command_line;
        }

        if ($this->register_pid) {
            // Make sure we have a Pid file
            $this->setPidFile();
        }

        if (!$this->command) {
            throw new ProcessException(tr('Cannot execute process, no command specified'));
        }

        $this->cached_command_line = $this->command . ' ' . implode(' ', $this->arguments);

        // Add wait
        if ($this->wait) {
            $this->cached_command_line = 'sleep ' . $this->wait . '; ' . $this->cached_command_line;
        }
        
        // Add timeout
        if ($this->timeout) {
            $this->cached_command_line = 'timeout --foreground ' . escapeshellarg($this->timeout) . ' ' . $this->cached_command_line;
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

        if ($this->register_pid) {
            // Make sure the PID will be registered
            $this->cached_command_line = 'bash -c "set -o pipefail; ' . $this->cached_command_line . '"; echo $$ > ' . $this->run_file;
        }

        return $this->cached_command_line;
    }
}