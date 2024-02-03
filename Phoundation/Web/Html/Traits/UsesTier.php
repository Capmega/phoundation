<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Enums\ContainerTier;


/**
 * UsesTier trait
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait UsesTier
{
    /**
     * Container value for this container
     *
     * @var ContainerTier $tier
     */
    protected ContainerTier $tier = ContainerTier::md;


    /**
     * Sets the type for this container
     *
     * @param ContainerTier $tier
     * @return static
     */
    public function setTier(ContainerTier $tier): static
    {
        $this->tier = $tier;
        return $this;
    }


    /**
     * Returns the type for this container
     *
     * @return ContainerTier
     */
    public function getTier(): ContainerTier
    {
        return $this->tier;
    }
}
