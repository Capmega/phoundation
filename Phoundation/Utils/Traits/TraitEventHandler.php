<?php

/**
 * Trait TraitEventHandler
 *
 * This trait adds support for managing an $o_events object in your class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openpath.org/licenses/GPL-2.0 GNU Public License, Events 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Traits;

use Phoundation\Utils\Interfaces\EventsInterface;
use Phoundation\Utils\Events;
use Stringable;


trait TraitEventHandler
{
    /**
     * The path to use
     *
     * @var EventsInterface|null $o_events
     */
    protected ?EventsInterface $o_events = null;


    /**
     * Returns the version object
     *
     * @return EventsInterface
     */
    public function getEventsObject(): EventsInterface
    {
        if (empty($this->o_events)) {
            $this->o_events = new Events();
        }

        return $this->o_events;
    }


    /**
     * Sets the version object
     *
     * @param EventsInterface $o_events The new Events handler class for this object
     *
     * @return static
     */
    public function setEventsObject(EventsInterface $o_events): static
    {
        $this->o_events = $o_events;
        return $this;
    }


    /**
     * Adds the handler for the specified event
     *
     * @param string   $event     The name for this event handler
     * @param callable $handler   The handler callback for the specified event
     * @param bool     $exception Will not throw an exception if the specified event has already been registered before
     * @return TraitEventHandler
     */
    public function addEventHandler(string $event, callable $handler, bool $exception = true): static
    {
        $this->getEventsObject()->add($handler, $event, exception: $exception);
        return $this;
    }


    /**
     * Triggers the specified event
     *
     * If the event exists in the Event handler object, the event will be executed
     *
     * @param Stringable|string|float|int $event     The event key for which the callback should be executed
     * @param mixed                       $values    The values to pass along to the callback function
     * @param bool                        $exception If true, will throw a NotExistsException if the specified event
     *                                               does not exist
     * @return mixed                                 The return value from the event callback, if available. NULL
     *                                               otherwise
     */
    public function triggerEvent(Stringable|string|float|int $event, mixed $values, bool $exception = false): static
    {
        $this->getEventsObject()->trigger($event, $values, $exception);
        return $this;
    }
}
