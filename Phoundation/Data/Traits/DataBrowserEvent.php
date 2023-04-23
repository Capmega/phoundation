<?php

namespace Phoundation\Data\Traits;

use Phoundation\Web\Http\Html\Enums\BrowserEvent;

/**
 * Trait DataEvent
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataBrowserEvent
{
    protected BrowserEvent $browser_event;

    /**
     * Returns the source
     *
     * @return BrowserEvent
     */
    public function getBrowserEvent(): BrowserEvent
    {
        return $this->browser_event;
    }


    /**
     * Sets the source
     *
     * @param BrowserEvent $browser_event
     * @return static
     */
    public function setBrowserEvent(BrowserEvent $browser_event): static
    {
        $this->browser_event = $browser_event;
        return $this;
    }
}