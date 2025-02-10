<?php

/**
 * Trait TraitDataBrowserEvent
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Enums\EnumBrowserEvent;


trait TraitDataBrowserEvent
{
    /**
     * Tracks the browser event
     *
     * @var EnumBrowserEvent $browser_event
     */
    protected EnumBrowserEvent $browser_event;


    /**
     * Returns the browser event
     *
     * @return EnumBrowserEvent|null
     */
    public function getBrowserEvent(): ?EnumBrowserEvent
    {
        return $this->browser_event;
    }


    /**
     * Sets the browser event
     *
     * @param EnumBrowserEvent|null $browser_event
     *
     * @return static
     */
    public function setBrowserEvent(?EnumBrowserEvent $browser_event): static
    {
        $this->browser_event = get_null($browser_event);

        return $this;
    }
}
