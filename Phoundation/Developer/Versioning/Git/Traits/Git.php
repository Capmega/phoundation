<?php

namespace Phoundation\Developer\Versioning\Git\Traits;

use Phoundation\Processes\Process;


/**
 * Trait Git
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Versioning
 */
trait Git
{
    /**
     * The git process
     *
     * @var Process $git
     */
    protected Process $git;



    /**
     * Returns the GIT process
     *
     * @return Process
     */
    public function getGit(): Process
    {
        return $this->git;
    }
}