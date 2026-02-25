<?php

/**
 * Trait TraitEventHandler
 *
 * This trait adds support for managing an $_events object in your class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openpath.org/licenses/GPL-2.0 GNU Public License, Events 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Traits;

use Phoundation\Data\Exception\IteratorKeyExistsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Interfaces\EventsInterface;
use Phoundation\Utils\Events;
use Stringable;


trait TraitEventHandler
{
    /**
     * The path to use
     *
     * @var EventsInterface|null $_events
     */
    protected ?EventsInterface $_events = null;


    /**
     * Returns the version object
     *
     * @return EventsInterface
     */
    public function getEventsObject(): EventsInterface
    {
        if (empty($this->_events)) {
            $this->_events = new Events();
        }

        return $this->_events;
    }


    /**
     * Sets the version object
     *
     * @param EventsInterface $_events The new Events handler class for this object
     *
     * @return static
     */
    public function setEventsObject(EventsInterface $_events): static
    {
        $this->_events = $_events;
        return $this;
    }


    /**
     * Triggers the specified event
     *
     * If the event exists in the Event handler object, the event will be executed
     *
     * @param Stringable|string|float|int $event            The event key for which the callback should be executed
     * @param mixed                       $values    [null] The values to pass along to the callback function
     * @param bool                        $exception [true] If true, will throw a NotExistsException if the specified
     *                                                      event does not exist
     * @return mixed                                        The return value from the event callback, if available. NULL
     *                                                      otherwise
     */
    public function triggerEvent(Stringable|string|float|int $event, mixed $values = null, bool $exception = false): static
    {
        $this->getEventsObject()->trigger($event, $values, $exception);
        return $this;
    }


    /**
     * Adds the handler for the specified event
     *
     * @param string   $event     The name for this event handler
     * @param callable $handler   The handler callback for the specified event
     * @param bool     $exception Will not throw an exception if the specified event has already been registered before
     *
     * @return static
     */
    public function addEventHandler(string $event, callable $handler, bool $exception = true): static
    {
        $this->getEventsObject()->add($handler, $event, true, $exception);
        return $this;
    }


    /**
     * Sets the handler for the specified event name
     *
     * @param array|string $events           The name of the event
     * @param callable     $handler          The handler function that will be executed when this event is triggered
     * @param bool         $exception [true] If true, will throw an IteratorKeyExistsException
     * @return static
     * @throws IteratorKeyExistsException
     */
    public function setEventHandlers(array|string $events, callable $handler, bool $exception = true): static
    {
        $events = Arrays::force($events);

        switch (count($events)) {
            case 0:
                break;

            case 1:
                $this->getEventsObject()->add($handler, array_pop($events), true, $exception);
                break;

            default:
                // Set this handler for multiple events
                foreach ($events as $event) {
                    $this->setEventHandlers($event, $handler, $exception);
                }
        }

        return $this;
    }
}
