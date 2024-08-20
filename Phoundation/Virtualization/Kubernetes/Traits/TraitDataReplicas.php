<?php

/**
 * Trait TraitDataReplicas
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */


declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Traits;


trait TraitDataReplicas
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
     *
     * @return static
     */
    public function setReplicas(int $replicas): static
    {
        $this->replicas = $replicas;

        return $this;
    }
}
