<?php

/**
 * Trait TraitDataObjectBtrfs
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



trait TraitDataObjectBtrfs
{
    /**
     * The process_name for this object
     *
     * @var BtrfsInterface|null $_btrfs
     */
    protected ?BtrfsInterface $_btrfs = null;


    /**
     * Returns the process name
     *
     * @return BtrfsInterface|null
     */
    public function getBtrfsObject(): ?BtrfsInterface
    {
        return $this->_process;
    }


    /**
     * Sets the process name
     *
     * @param BtrfsInterface|null $_btrfs
     *
     * @return static
     */
    protected function setBtrfsObject(?BtrfsInterface $_btrfs): static
    {
        $this->_process = $_btrfs;
        return $this;
    }
}
