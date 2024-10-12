<?php

/**
 * Trait TraitDataLogLevel
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataLogLevel
{
    /**
     * Tracks the log level to use
     *
     * @var int $log_level
     */
    protected int $log_level = 0;


    /**
     * Returns the server log_level
     *
     * @return int
     */
    public function getLogLevel(): int
    {
        return $this->log_level;
    }


    /**
     * Sets the log level
     *
     * @param int $log_level The log level to use
     */
    public function setLogLevel(int $log_level): static
    {
        $this->log_level = $log_level;
        return $this;
    }
}
