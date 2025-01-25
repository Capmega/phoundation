<?php

namespace Phoundation\Web\Server\Interfaces;


interface VirtualhostInterface
{
/**
     * @return $this
     */
    public function installFile(): static;
}
