<?php

/**
 * Class Service
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 * @uses      ProcessVariables
 */


declare(strict_types=1);

namespace Phoundation\Os\Services;

use Phoundation\Os\Processes\Interfaces\ProcessInterface;


class Service extends ServiceCore
{
    /**
     * Service class constructor
     *
     * @param ProcessInterface $process
     */
    public function __construct(ProcessInterface $process) {
        $this->process = $process;
    }


    /**
     * Returns a new Service class object
     *
     * @param ProcessInterface $process
     *
     * @return static
     */
    public static function new(ProcessInterface $process): static
    {
        return new static($process);
    }
}
