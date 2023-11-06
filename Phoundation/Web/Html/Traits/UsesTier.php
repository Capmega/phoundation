<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Enums\DisplayTier;


/**
 * UsesTier trait
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait UsesTier
{
    /**
     * Container value for this container
     *
     * @var DisplayTier $tier
     */
    protected DisplayTier $tier = DisplayTier::xxl;

    
    /**
     * Sets the type for this container
     *
     * @param DisplayTier $tier
     * @return static
     */
    public function setTier(DisplayTier $tier): static
    {
        $this->tier = $tier;
        return $this;
    }


    /**
     * Returns the type for this container
     *
     * @return DisplayTier
     */
    public function getTier(): DisplayTier
    {
        return $this->tier;
    }
}