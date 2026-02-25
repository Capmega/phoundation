<?php

/**
 * Trait TraitDataObjectProcess
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

use Phoundation\Os\Processes\Interfaces\ProcessInterface;


trait TraitDataObjectProcess
{
    /**
     * The process_name for this object
     *
     * @var ProcessInterface|null $_process
     */
    protected ?ProcessInterface $_process = null;


    /**
     * Returns the process name
     *
     * @return ProcessInterface|null
     */
    public function getProcessObject(): ?ProcessInterface
    {
        return $this->_process;
    }


    /**
     * Sets the process name
     *
     * @param ProcessInterface|null $_process
     *
     * @return static
     */
    protected function setProcessObject(?ProcessInterface $_process): static
    {
        $this->_process = $_process;
        return $this;
    }
}
