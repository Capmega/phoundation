<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands\Interfaces;

use Phoundation\Os\Processes\Interfaces\WorkersCoreInterface;

interface PhoInterface extends WorkersCoreInterface
{
    /**
     * Returns the Phoundation commands
     *
     * @return array|null
     */
    public function getPhoCommands(): ?array;

    /**
     * Sets the Phoundation commands
     *
     * @param array|string|null $pho_commands
     * @return static
     */
    public function setPhoCommands(array|string|null $pho_commands): static;
}
