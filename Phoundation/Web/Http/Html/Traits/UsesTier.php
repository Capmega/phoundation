<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Traits;

use Phoundation\Web\Http\Html\Enums\DisplayTier;
use Phoundation\Web\Http\Html\Interfaces\InterfaceDisplayTier;


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
     * @var InterfaceDisplayTier $tier
     */
    protected InterfaceDisplayTier $tier = DisplayTier::xxl;

    
    /**
     * Sets the type for this container
     *
     * @param InterfaceDisplayTier $tier
     * @return static
     */
    public function setTier(InterfaceDisplayTier $tier): static
    {
        $this->tier = $tier;
        return $this;
    }


    /**
     * Returns the type for this container
     *
     * @return InterfaceDisplayTier
     */
    public function getTier(): InterfaceDisplayTier
    {
        return $this->tier;
    }
}