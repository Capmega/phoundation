<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataServiceName;
use Phoundation\Processes\Commands\Exception\CommandsException;


/**
 * Class Service
 *
 * service (Linux services management) command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Service extends Command
{
    use DataServiceName;


    /**
     * Execute the service status command operation and return the specified service status
     *
     * @return array
     */
    public function status(): array
    {
        $this->validateService();

        // Return status result
        return $this->executeService('status');
    }


    /**
     * Execute the service restart command operation and return the specified service status
     *
     * @return static
     */
    public function restart(): static
    {
        $this->validateService();

        Log::action(tr('Restarting service ":service"', [
            ':service' => $this->service_name
        ]));

        // Restart the service
        $this->executeService('restart');
        return $this;
    }


    /**
     * Execute the service restart command operation and return the specified service status
     *
     * @return static
     */
    public function start(): static
    {
        $this->validateService();

        Log::action(tr('Starting service ":service"', [
            ':service' => $this->service_name
        ]));

        // Start the service
        $this->executeService('start');
        return $this;
    }


    /**
     * Execute the service restart command operation and return the specified service status
     *
     * @return static
     */
    public function stop(): static
    {
        $this->validateService();

        Log::action(tr('Stopping service ":service"', [
            ':service' => $this->service_name
        ]));

        $this->executeService('stop');
        return $this;
    }


    /**
     * Ensures all is fine before we execute the service command
     *
     * @return void
     */
    protected function validateService(): void
    {
        if (!$this->service_name) {
            throw new CommandsException(tr('Cannot execute service command, no service name specified'));
        }
    }


    /**
     * Execute the service command
     *
     * @param string $action
     * @return array
     */
    protected function executeService(string $action): array
    {
        // Restart the service
        return $this
            ->clearArguments()
            ->setSudo(true)
            ->setInternalCommand('service')
            ->addArgument($this->service_name)
            ->addArgument($action)
            ->executeReturnArray();
    }
}