<?php

/**
 * Class ProcessThis
 *
 * This class embodies the current process that can be executed anew
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 * @uses      ProcessVariables
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Data\Traits\TraitStaticMethodNew;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;


class ProcessThis extends ProcessCore implements ProcessInterface
{
    use TraitStaticMethodNew;


    /**
     * ProcessesThis constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }
}
