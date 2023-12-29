<?php

namespace Phoundation\Os\Processes;

use Phoundation\Filesystem\Interfaces\RestrictionsInterface;


/**
 * Class WorkersCore
 *
 * This class can manage worker processes running in the background
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 * @uses ProcessVariables
 */
class Workers extends WorkersCore
{
    /**
     * Returns a new Workers object
     *
     * @param string|null $command
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static
     */
    public static function new(?string $command, RestrictionsInterface|array|string|null $restrictions = null): static
    {
        $static = new static($restrictions);
        return $static->setCommand($command);
    }
}

