<?php

namespace Phoundation\Processes;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Exception\ProcessFailedException;



/**
 * Class ProcessCommands
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods to manage Linux
 * processes.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class ProcessCommands extends Commands
{
    /**
     * Returns the process id for the specified command
     *
     * @note Returns NULL if the process wasn't found
     * @param string $process
     * @return ?int
     */
    public function pgrep(string $process): ?int
    {
        try {
            $output = Processes::create('pgrep', $this->server, true)
                ->addArgument($process)
                ->setTimeout(1)
                ->executeReturnArray();
            $output = array_pop($output);

            if (!$output or !is_numeric($output)) {
                return null;
            }

            return (integer) $output;

        } catch (ProcessFailedException $e) {
            return null;
        }
    }



    /**
     * Returns the process id's for all children of the specified parent process id
     *
     * @note This method will also return the PID for the pgrep command that was used to create this list!
     * @param int $pid
     * @return array
     */
    public function getChildren(int $pid): array
    {
        try {
            if ($pid < 0) {
                throw new OutOfBoundsException(tr('The specified process id ":pid" is invalid. Please specify a positive integer', [':pid' => $pid]));
            }

            $children = Processes::create('pgrep', $this->server, true)
                ->addArguments(['-P', $pid])
                ->setTimeout(1)
                ->executeReturnArray();

            // Remove the pgrep command PID
            unset($children[0]);

            return $children;

        } catch (ProcessFailedException $e) {
            // The command id failed
            Commands::handleException('pgrep', $e);
        }
    }



    /**
     * Sends the specified signal to the specified process ids
     *
     * @param int $signal
     * @param array|int $pids
     * @return void
     */
    public function killPid(int $signal, array|int $pids): void
    {
        try {
            // Validate arguments
            if (($signal < 1) or ($signal > 64)) {
                throw new OutOfBoundsException(tr('Specified signal ":signal" is invalid, ensure it is an integer number between 1 and 64', [':signal' => $signal]));
            }

            foreach ($pids as $pid) {
                if (!is_integer($pid)) {
                    throw new OutOfBoundsException(tr('Specified pid ":pid" is invalid, it should be an integer number 2 or higher', [':pid' => $pid]));
                }

                if (($pid < 2)) {
                    throw new OutOfBoundsException(tr('Specified pid ":pid" is invalid, it should be an integer number 2 or higher', [':pid' => $pid]));
                }
            }

            Processes::create('kill', $this->server, true)
                ->addArgument('-' . $signal)
                ->addArguments($pids)
                ->setTimeout(10)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command kill failed
            Commands::handleException('kill', $e);
        }
    }



    /**
     * Sends the specified signal to the specified process names
     *
     * @param int $signal
     * @param array|string $processes
     * @return void
     */
    public function killProcesses(int $signal, array|string $processes): void
    {
        try {
            // Validate arguments
            if (($signal < 1) or ($signal > 64)) {
                throw new OutOfBoundsException(tr('Specified signal ":signal" is invalid, ensure it is an integer number between 1 and 64', [':signal' => $signal]));
            }

            foreach ($processes as $process) {
                if (!is_scalar($process)) {
                    throw new OutOfBoundsException(tr('Specified process ":process" is invalid, it should be a string', [':process' => $process]));
                }

                if (strlen($process) < 2) {
                    throw new OutOfBoundsException(tr('Specified process ":process" is invalid, it should be 2 characters or more', [':process' => $process]));
                }
            }

            Processes::create('pkill', $this->server, true)
                ->addArgument('-' . $signal)
                ->addArguments($processes)
                ->setTimeout(10)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command pkill failed
            Commands::handleException('pkill', $e);
        }
    }


    /**
     * Returns limited process information about the specified PID
     *
     * @param int $pid
     * @return array|null
     */
    public function ps(int $pid): ?array
    {
        try {
            // Validate arguments
            if ($pid < 1) {
                throw new OutOfBoundsException(tr('Specified pid ":pid" is invalid, it should be an integer number 1 or higher', [':pid' => $pid]));
            }

            $data = Processes::create('ps', $this->server, true)
                ->addArguments(['-p', $pid, '-o', 'pid,ppid,comm,cmd,args'])
                ->setTimeout(1)
                ->executeReturnArray();

            if (count($data) <= 1) {
                //only the top line was returned, so the specified PID was not found
                return null;
            }

            $data = array_pop($data);
show($data);
            return [
                'pid'  => trim(substr($data, 0,8)),
                'ppid' => trim(substr($data, 8, 8)),
                'comm' => trim(substr($data, 16, 16)),
                'cmd'  => trim(substr($data, 28, 32)),
                'args' => trim(substr($data, 60))
            ];

        } catch (ProcessFailedException $e) {
            // The command pkill failed
            Commands::handleException('pkill', $e);
        }
    }



    /**
     * Returns all process information about the specified PID
     *
     * @param int $pid
     * @return array|null
     */
    public function psFull(int $pid): ?array
    {
        try {
            // Validate arguments
            if ($pid < 1) {
                throw new OutOfBoundsException(tr('Specified pid ":pid" is invalid, it should be an integer number 1 or higher', [':pid' => $pid]));
            }

            $data = Processes::create('ps', $this->server, true)
                ->addArguments(['-p', $pid, '-o', 'pid,ppid,uid,gid,comm,cmd,exe,args,nice,fuid,%cpu,%mem,size,cputime,cputimes,drs,etime,etimes,euid,egid,egroup,start_time,bsdtime,state,stat,time,vsize'])
                ->setTimeout(1)
                ->executeReturnArray();

            if (count($data) <= 1) {
                //only the top line was returned, so the specified PID was not found
                return null;
            }

            $data = array_pop($data);

            $return = [
//                'pid'        => trim(substr($data, , )),
//                'ppid'       => trim(substr($data, , )),
//                'uid'        => trim(substr($data, , )),
//                'gid'        => trim(substr($data, , )),
//                'comm'       => trim(substr($data, , )),
//                'cmd'        => trim(substr($data, , )),
//                'exe'        => trim(substr($data, , )),
//                'args'       => trim(substr($data, , )),
//                'nice'       => trim(substr($data, , )),
//                'fuid'       => trim(substr($data, , )),
//                '%cpu'       => trim(substr($data, , )),
//                '%mem'       => trim(substr($data, , )),
//                'size'       => trim(substr($data, , )),
//                'cputime'    => trim(substr($data, , )),
//                'cputimes'   => trim(substr($data, , )),
//                'drs'        => trim(substr($data, , )),
//                'etime'      => trim(substr($data, , )),
//                'etimes'     => trim(substr($data, , )),
//                'euid'       => trim(substr($data, , )),
//                'egid'       => trim(substr($data, , )),
//                'egroup'     => trim(substr($data, , )),
//                'start_time' => trim(substr($data, , )),
//                'bsdtime'    => trim(substr($data, , )),
//                'state'      => trim(substr($data, , )),
//                'stat'       => trim(substr($data, , )),
//                'time'       => trim(substr($data, , )),
//                'vsize'      => substr($data, , )
            ];
showdie($return);

//      1       0     0     0 systemd         /lib/systemd/systemd splash -                           /lib/systemd/systemd splash   0     0  0.0  0.0 21496 00:00:23       23 168232 22-06:40:52 1924852    0     0 root     Sep20   0:23 S Ss   00:00:23 168232

        } catch (ProcessFailedException $e) {
            // The command pkill failed
            Commands::handleException('pkill', $e);
        }
    }
}