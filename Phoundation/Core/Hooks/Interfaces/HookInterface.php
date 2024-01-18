<?php

namespace Phoundation\Core\Hooks\Interfaces;

use Phoundation\Core\Hooks\Hook;


/**
 * Hook class
 *
 * This class can manage and (attempt to) execute specified hook scripts.
 *
 * Hook scripts are optional scripts that will be executed if they exist. Hook scripts are located in
 * DIRECTORY_DATA/system/hooks/HOOK and DIRECTORY_DATA/system/hooks/CLASS/HOOK. CLASS is an identifier for multiple hook
 * scripts that all have to do with the same system, to group them together. HOOK is the script to be executed
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
interface HookInterface
{
    /**
     * Attempts to execute the specified hooks
     *
     * @param array|string $hooks
     * @return $this
     */
    public function execute(array|string $hooks, ?array $params = null): static;
}
