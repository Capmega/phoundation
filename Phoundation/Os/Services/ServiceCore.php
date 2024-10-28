<?php

/**
 * Class ServiceCore
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Services;

use Phoundation\Os\Processes\Interfaces\ProcessInterface;
use Phoundation\Os\Processes\ProcessCore;

class ServiceCore extends ProcessCore
{
    protected ProcessInterface $process;


    /**
     *
     *
     * @return static
     */
    public function start(): static
    {

    }


    /**
     *
     *
     * @return static
     */
    public function stop(): static
    {

    }


    /**
     * Returns true if the current service is already running, false otherwise
     *
     * @return bool
     */
    public function isRunning(): bool
    {

    }
}
