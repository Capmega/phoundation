<?php

/**
 * Trait TraitDataBrowserEvent
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Enums\EnumBrowserEvent;

trait TraitDataBrowserEvent
{
    /**
     *
     *
     * @var EnumBrowserEvent $browser_event
     */
    protected EnumBrowserEvent $browser_event;


    /**
     * Returns the source
     *
     * @return EnumBrowserEvent
     */
    public function getBrowserEvent(): EnumBrowserEvent
    {
        return $this->browser_event;
    }


    /**
     * Sets the source
     *
     * @param EnumBrowserEvent $browser_event
     *
     * @return static
     */
    public function setBrowserEvent(EnumBrowserEvent $browser_event): static
    {
        $this->browser_event = $browser_event;

        return $this;
    }
}