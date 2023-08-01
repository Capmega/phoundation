<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Traits;


/**
 * Trait DataReplicas
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
trait DataReplicas
{
    protected int $replicas = 1;


    /**
     * Returns the replicas
     *
     * @return int
     */
    public function getReplicas(): int
    {
        return $this->replicas;
    }


    /**
     * Sets the replicas
     *
     * @param int $replicas
     * @return $this
     */
    public function setReplicas(int $replicas): static
    {
        $this->replicas = $replicas;
        return $this;
    }
}