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
     * @var ProcessInterface|null $o_process
     */
    protected ?ProcessInterface $o_process = null;


    /**
     * Returns the process name
     *
     * @return ProcessInterface|null
     */
    public function getProcessObject(): ?ProcessInterface
    {
        return $this->o_process;
    }


    /**
     * Sets the process name
     *
     * @param ProcessInterface|null $o_process
     *
     * @return static
     */
    protected function setProcessObject(?ProcessInterface $o_process): static
    {
        $this->o_process = $o_process;
        return $this;
    }
}
