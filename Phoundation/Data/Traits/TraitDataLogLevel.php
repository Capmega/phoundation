<?php

/**
 * Trait TraitDataLogLevel
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Exception\OutOfBoundsException;


trait TraitDataLogLevel
{
    /**
     * Tracks the log level to use
     *
     * @var int $log_level
     */
    protected int $log_level = 5;


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
     * @param int $level The log level to use
     * @param int $max_level
     *
     * @return static
     */
    public function setLogLevel(int $level, int $max_level = 9): static
    {
        if (($level < 1) or ($level > 9)) {
            throw new OutOfBoundsException(tr('Invalid log level ":value" specified, the value must be between 0 and 9', [
                ':value' => $level
            ]));
        }

        // By default, the log level may not surpass the maximum specified log level
        if ($level > $max_level) {
            $level = $max_level;
        }

        $this->log_level = $level;
        return $this;
    }
}
