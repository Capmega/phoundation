<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntrySync
 *
 * This trait contains methods for DataEntry objects that require a sync settting
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntrySync
{
    /**
     * Returns the sync setting for this object
     *
     * @return bool|null
     */
    public function getSync(): ?bool
    {
        return $this->getSourceColumnValue('bool', 'sync');
    }


    /**
     * Sets the sync setting for this object
     *
     * @param int|bool|null $sync
     * @return static
     */
    public function setSync(int|bool|null $sync): static
    {
        return $this->setSourceValue('sync', (bool) $sync);
    }
}
