<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataInsertUpdate
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://openrandom_id.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataInsertUpdate
{
    /**
     * Tracks whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @var bool $insert_update
     */
    protected bool $insert_update = false;


    /**
     * Returns whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @return bool
     */
    public function getInsertUpdate(): bool
    {
        return $this->insert_update;
    }


    /**
     * Sets whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @param bool $insert_update
     * @return static
     */
    public function setInsertUpdate(bool $insert_update): static
    {
        $this->insert_update = $insert_update;
        return $this;
    }
}