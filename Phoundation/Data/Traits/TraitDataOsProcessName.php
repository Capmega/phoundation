<?php

/**
 * Trait TraitDataOsProcessName
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openos_process_name.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Cli\CliCommand;
use Phoundation\Utils\Strings;

trait TraitDataOsProcessName
{
    /**
     * The os_process_name to use
     *
     * @var string|null $os_process_name
     */
    protected ?string $os_process_name = null;


    /**
     * Returns the os_process_name
     *
     * @return string|null
     */
    public function getOsProcessName(): ?string
    {
        return $this->os_process_name;
    }


    /**
     * Sets the os_process_name
     *
     * @param string|null $os_process_name
     *
     * @return static
     */
    public function setOsProcessName(?string $os_process_name): static
    {
        $this->os_process_name = $os_process_name;
        return $this;
    }


    /**
     * Detects and sets the process name for this object
     *
     * @return static
     */
    public function detectOsProcessName(): static
    {
        return $this->setOsProcessName('pho-' . Strings::force(CliCommand::getCommands(), '-'));
    }
}
