<?php

namespace Phoundation\Os\Services\Interfaces;

interface ServiceInterface
{
    /**
     *
     *
     * @return static
     */
    public function start(): static;

    /**
     * Stops the service for the current command
     *
     * @return static
     */
    public function stop(): static;

    /**
     * Returns true if the current service is already running, false otherwise
     *
     * @return bool
     */
    public function isRunning(): bool;
}