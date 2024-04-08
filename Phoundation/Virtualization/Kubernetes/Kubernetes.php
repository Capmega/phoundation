<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Process;
use Phoundation\Virtualization\Kubernetes\Enums\Services;

/**
 * Class Kubernetes
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */
class Kubernetes
{
    /**
     * What service is used to manage the kubernetes cluster
     *
     * @var Services $service
     */
    protected Services $service;

    /**
     * The command that controls the Kubernetes cluster
     *
     * @var string $command
     */
    protected string $command;

    /**
     * The timeout for the start command
     *
     * @var int $start_timeout
     */
    protected int $start_timeout = 60;


    /**
     * Kubernetes class constructor
     */
    protected function __construct(Services $service = Services::minikube)
    {
        switch ($service) {
            case Services::minikube:
                $this->service = $service;
                $this->command = 'minikube';
        }
    }


    /**
     * Returns the timeout value for starting the kubernetes cluster.
     *
     * If the process requires more time than the specified timeout value, it will be terminated automatically. Set to
     * 0 seconds  to disable, defaults to 60 seconds
     *
     * @return int
     */
    public function getStartTimeout(): int
    {
        return $this->start_timeout;
    }


    /**
     * Sets the timeout value for starting the kubernetes cluster.
     *
     * If the process requires more time than the specified timeout value, it will be terminated automatically. Set to
     * 0 seconds  to disable, defaults to 60 seconds
     *
     * @param int $timeout
     *
     * @return static
     */
    public function setStartTimeout(int $timeout): static
    {
        if (!is_natural($timeout, 0)) {
            throw new OutOfBoundsException(tr('The specified timeout ":timeout" is invalid, it must be a natural number 0 or higher', [
                ':timeout' => $timeout,
            ]));
        }
        $this->start_timeout = $timeout;

        return $this;
    }


    /**
     * Starts the kubernetes service
     *
     * @param EnumExecuteMethod $method
     */
    public function start(EnumExecuteMethod $method = EnumExecuteMethod::passthru): void
    {
        Process::new($this->command)
               ->setTimeout($this->start_timeout)
               ->addArguments('start')
               ->execute($method);
    }


    /**
     * Returns a new Kubernetes service object
     */
    public static function new(Services $service = Services::minikube): static
    {
        return new Kubernetes($service);
    }


    /**
     * Stops the kubernetes service
     *
     * @param EnumExecuteMethod $method
     */
    public function stop(EnumExecuteMethod $method = EnumExecuteMethod::passthru): void
    {
        Process::new($this->command)
               ->addArguments('stop')
               ->execute($method);
    }


    /**
     * Returns the status of the kubernetes service
     *
     * @return Status
     */
    public function getStatus(): Status
    {
        $output = Process::new($this->command)
                         ->addArguments('status')
                         ->executeReturnArray();

        return new Status($output);
    }


    /**
     * Deletes the local kubernetes cluster
     *
     * @param EnumExecuteMethod $method
     *
     * @return void
     */
    public function delete(EnumExecuteMethod $method = EnumExecuteMethod::passthru): void
    {
        Process::new($this->command)
               ->addArguments('delete')
               ->execute($method);
    }


    /**
     * Pauses the local kubernetes cluster
     *
     * @param EnumExecuteMethod $method
     */
    public function pause(EnumExecuteMethod $method = EnumExecuteMethod::passthru): void
    {
        Process::new($this->command)
               ->addArguments('pause')
               ->execute($method);
    }


    /**
     * Unpauses the local kubernetes cluster
     *
     * @param EnumExecuteMethod $method
     */
    public function unpause(EnumExecuteMethod $method = EnumExecuteMethod::passthru): void
    {
        Process::new($this->command)
               ->addArguments('unpause')
               ->execute($method);
    }


    /**
     * Starts the kubernetes dashboard in a background process
     */
    public function dashboard(): void
    {
        Process::new($this->command)
               ->addArguments('dashboard')
               ->executeBackground();
    }
}