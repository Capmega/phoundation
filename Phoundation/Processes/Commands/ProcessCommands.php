<?php

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Exception\ProcessFailedException;
use Phoundation\Processes\Process;


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
class ProcessCommands extends Command
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
            $output = Process::new('pgrep', $this->server)
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

            $children = Process::new('pgrep', $this->server)
                ->addArguments(['-P', $pid])
                ->setTimeout(1)
                ->executeReturnArray();

            // Remove the pgrep command PID
            unset($children[0]);

            return $children;

        } catch (ProcessFailedException $e) {
            // The command id failed
            Command::handleException('pgrep', $e);
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

            Process::new('kill', $this->server)
                ->addArgument('-' . $signal)
                ->addArguments($pids)
                ->setTimeout(10)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command kill failed
            Command::handleException('kill', $e);
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

            Process::new('pkill', $this->server)
                ->addArgument('-' . $signal)
                ->addArguments($processes)
                ->setTimeout(10)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command pkill failed
            Command::handleException('pkill', $e);
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

            $data = Process::new('ps', $this->server)
                ->addArguments(['-p', $pid, '--no-headers', '-o', 'pid,ppid,comm,cmd,args'])
                ->setTimeout(1)
                ->executeReturnArray();

            if (count($data) < 1) {
                //only the top line was returned, so the specified PID was not found
                return null;
            }

            $data = array_pop($data);

            return [
                'pid'  => (int) trim(substr($data, 0,8)),
                'ppid' => (int) trim(substr($data, 8, 8)),
                'comm' =>       trim(substr($data, 16, 16)),
                'cmd'  =>       trim(substr($data, 28, 32)),
                'args' =>       trim(substr($data, 60))
            ];

        } catch (ProcessFailedException $e) {
            // The command pkill failed
            Command::handleException('pkill', $e);
        }
    }



    /**
     * Returns all process information about the specified PID
     *
     * @note The parsing of this data is currently a mess as ps has no proper output formatting beyond "I'll separate
     *       the fields by adding a space" which is really fun with arguments that have spaces too. This will be
     *       improved at some later time when this method will be more needed
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

            $data = Process::new('ps', $this->server)
                ->addArguments(['-p', $pid, '--no-headers', '-o', 'pid:1,ppid:1,uid:1,gid:1,nice:1,fuid:1,%cpu:1,%mem:1,size:1,cputime:1,cputimes:1,drs:1,etime:1,etimes:1,euid:1,egid:1,egroup:1,start_time:1,bsdtime:1,state:1,stat:1,time:1,vsize:1,rss:1,args'])
                ->setTimeout(1)
                ->executeReturnArray();

            if (count($data) < 1) {
                //only the top line was returned, so the specified PID was not found
                return null;
            }

            $data = array_pop($data);
            $return = [];
            $return['pid']         = trim(Strings::until($data, ' '));
            $return['ppid']        = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['uid']         = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['gid']         = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['nice']        = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['fuid']        = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['%cpu']        = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['%mem']        = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['size']        = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['cputime']     = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['cputimes']    = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['drs']         = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['etime']       = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['etimes']      = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['euid']        = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['egid']        = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['egroup']      = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['start_time']  = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['bsdtime']     = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['state']       = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['stat']        = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['time']        = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['vsize']       = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['rss']         = trim(Strings::until($data = trim(Strings::from($data, ' ')), ' '));
            $return['args']        = trim(Strings::from ($data = trim(Strings::from($data, ' ')), ' '));

            // Fix datatypes
            $return['pid']    = (int)   $return['pid'];
            $return['ppid']   = (int)   $return['ppid'];
            $return['uid']    = (int)   $return['uid'];
            $return['gid']    = (int)   $return['gid'];
            $return['nice']   = (int)   $return['nice'];
            $return['fuid']   = (int)   $return['fuid'];
            $return['size']   = (int)   $return['size'];
            $return['etimes'] = (int)   $return['etimes'];
            $return['euid']   = (int)   $return['euid'];
            $return['egid']   = (int)   $return['egid'];
            $return['vsize']  = (int)   $return['vsize'];
            $return['rss']    = (int)   $return['rss'];
            $return['%cpu']   = (float) $return['%cpu'];
            $return['%mem']   = (float) $return['%mem'];


            $return['state_label'] = match ($return['state']) {
                'D' => tr('uninterruptible sleep (usually IO)'),
                'I' => tr('Idle kernel thread'),
                'R' => tr('running or runnable (on run queue)'),
                'S' => tr('interruptible sleep (waiting for an event to complete)'),
                'T' => tr('stopped by job control signal'),
                't' => tr('stopped by debugger during the tracing'),
                'W' => tr('paging (not valid since the 2.6.xx kernel)'),
                'X' => tr('dead (should never be seen)'),
                'Z' => tr('defunct ("zombie") process, terminated but not reaped by its parent'),
                default => tr('Unknown process state ":state" encountered', [':state' => $return['state']])
            };

        } catch (ProcessFailedException $e) {
            // The command pkill failed
            Command::handleException('pkill', $e);
        }
    }
}