<?php

declare(strict_types=1);

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
            $output = $this->process
                ->setCommand('pgrep')
                ->addArgument($process)
                ->setTimeout(1)
                ->executeReturnArray();

            $output = array_pop($output);

            if (!$output or !is_numeric($output)) {
                return null;
            }

            return (integer) $output;

        } catch (ProcessFailedException $e) {
            // TODO Check what error happened! What about read permission denied?
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

            $output = $this->process
                ->setCommand('pgrep')
                ->addArguments(['-P', $pid])
                ->setTimeout(1)
                ->executeReturnArray();

            // Remove the pgrep command PID
            unset($output[0]);

            return $output;

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

            $this->process
                ->setCommand('kill')
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

            $this->process
                ->setCommand('pkill')
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

            $output = $this->process
                ->setCommand('ps')
                ->addArguments(['-p', $pid, '--no-headers', '-o', 'pid,ppid,comm,cmd,args'])
                ->setTimeout(1)
                ->executeReturnArray();

            if (count($output) < 1) {
                //only the top line was returned, so the specified PID was not found
                return null;
            }

            $output = array_pop($output);

            return [
                'pid'  => (int) trim(substr($output, 0,8)),
                'ppid' => (int) trim(substr($output, 8, 8)),
                'comm' =>       trim(substr($output, 16, 16)),
                'cmd'  =>       trim(substr($output, 28, 32)),
                'args' =>       trim(substr($output, 60))
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

            $output = $this->process
                ->setCommand('ps')
                ->addArguments(['-p', $pid, '--no-headers', '-o', 'pid:1,ppid:1,uid:1,gid:1,nice:1,fuid:1,%cpu:1,%mem:1,size:1,cputime:1,cputimes:1,drs:1,etime:1,etimes:1,euid:1,egid:1,egroup:1,start_time:1,bsdtime:1,state:1,stat:1,time:1,vsize:1,rss:1,args'])
                ->setTimeout(1)
                ->executeReturnArray();

            if (count($output) < 1) {
                //only the top line was returned, so the specified PID was not found
                return null;
            }

            $output = array_pop($output);
            $return = [];
            
            $return['pid']         = trim(Strings::until($output, ' '));
            $return['ppid']        = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['uid']         = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['gid']         = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['nice']        = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['fuid']        = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['%cpu']        = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['%mem']        = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['size']        = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['cputime']     = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['cputimes']    = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['drs']         = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['etime']       = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['etimes']      = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['euid']        = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['egid']        = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['egroup']      = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['start_time']  = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['bsdtime']     = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['state']       = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['stat']        = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['time']        = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['vsize']       = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['rss']         = trim(Strings::until($output = trim(Strings::from($output, ' ')), ' '));
            $return['args']        = trim(Strings::from ($output = trim(Strings::from($output, ' ')), ' '));

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

            return $return;

        } catch (ProcessFailedException $e) {
            // The command pkill failed
            Command::handleException('pkill', $e);
        }
    }
}