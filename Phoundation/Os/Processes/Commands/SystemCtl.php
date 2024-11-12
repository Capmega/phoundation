<?php

/**
 * Class Mpg123
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\TraitDataFile;
use Phoundation\Data\Traits\TraitDataOsProcessName;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;
use Phoundation\Os\Services\Exception\ServiceException;


class SystemCtl extends Command
{
    use TraitDataOsProcessName;


    /**
     * Returns a pre-configured systemctl Process object
     *
     * @return static
     */
    protected function getProcessObject(): static
    {
        return $this->clearArguments()
                    ->setSudo(true)
                    ->setCommand('systemctl');
    }


    /**
     * Checks that the process name has been specified before executing a systemd command that requires a process name
     *
     * @param string $for_command
     * @return static
     */
    protected function checkProcessName(string $for_command): static
    {
        if ($this->getOsProcessName()) {
            return $this;
        }

        throw new ServiceException(tr('Cannot execute systemd command ":command", no process was specified', [
            ':command' => $for_command
        ]));
    }


    /**
     * Executes the specified systemctl process command and returns nothing
     *
     * @param string $command
     * @return static
     */
    protected function executeProcessCommandReturnNothing(string $command): static
    {
        return $this->getProcessObject()
                    ->checkProcessName($command)
                    ->addArgument([$command, $this->getOsProcessName()])
                    ->executeNoReturn();
    }


    /**
     * Executes the specified systemctl process command and returns the command output in array format
     *
     * @param string $command
     * @return array
     */
    protected function executeProcessCommandReturnArray(string $command): array
    {
        return $this->getProcessObject()
                    ->checkProcessName($command)
                    ->addArgument([$command, $this->getOsProcessName()])
                    ->executeReturnArray();
    }


    /**
     * Executes the specified systemctl command and returns nothing
     *
     * @param string $command
     * @return static
     */
    protected function executeCommandReturnNothing(string $command): static
    {
        return $this->getProcessObject()
                    ->addArgument([$command])
                    ->executeNoReturn();
    }


    /**
     * Executes the specified systemctl command and returns the command output in array format
     *
     * @param string $command
     * @return array
     */
    protected function executeCommandReturnArray(string $command): array
    {
        return $this->getProcessObject()
                    ->addArgument([$command])
                    ->executeReturnArray();
    }


    /**
     * Start the specified process
     *
     * @return static
     */
    public function enable(): static
    {
        return $this->executeProcessCommandReturnNothing('enable');
    }


    /**
     * Start the specified process
     *
     * @return static
     */
    public function disable(): static
    {
        return $this->executeProcessCommandReturnNothing('disable');
    }


    /**
     * Start the specified process
     *
     * @return static
     */
    public function start(): static
    {
        return $this->executeProcessCommandReturnNothing('start');
    }


    /**
     * Restart the specified process
     *
     * @return static
     */
    public function restart(): static
    {
        return $this->executeProcessCommandReturnNothing('restart');
    }


    /**
     * Stop the specified process
     *
     * @return static
     */
    public function stop(): static
    {
        return $this->executeProcessCommandReturnNothing('stop');
    }


    /**
     * Returns status information about this process
     *
     * @return array
     */
    public function status(): array
    {
        return $this->executeProcessCommandReturnArray('status');
    }


    /**
     * Returns detailed information about this process
     *
     * @return array
     */
    public function show(): array
    {
        return $this->executeProcessCommandReturnArray('show');
    }


    /**
     * Reboot this machine
     *
     * @return static
     */
    public function reboot(): static
    {
        return $this->executeCommandReturnNothing('reboot');
    }


    /**
     * Soft reboot this machine
     *
     * @return static
     */
    public function softReboot(): static
    {
        return $this->executeCommandReturnNothing('soft-reboot');
    }


    /**
     * Suspend this machine
     *
     * @return static
     */
    public function suspend(): static
    {
        return $this->executeCommandReturnNothing('suspend');
    }
}
