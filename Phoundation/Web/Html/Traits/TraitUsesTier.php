<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Enums\EnumContainerTier;


/**
 * Trait TraitUsesTier
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait TraitUsesTier
{
    /**
     * Container value for this container
     *
     * @var EnumContainerTier $tier
     */
    protected EnumContainerTier $tier = EnumContainerTier::md;


    /**
     * Sets the type for this container
     *
     * @param EnumContainerTier $tier
     * @return static
     */
    public function setTier(EnumContainerTier $tier): static
    {
        $this->tier = $tier;
        return $this;
    }


    /**
     * Returns the type for this container
     *
     * @return EnumContainerTier
     */
    public function getTier(): EnumContainerTier
    {
        return $this->tier;
    }
}
