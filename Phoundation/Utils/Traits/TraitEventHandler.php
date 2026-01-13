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

use Phoundation\Data\Exception\EventException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Interfaces\EventsInterface;
use Phoundation\Utils\Events;
use Stringable;


trait TraitDataEventHandler
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
     * @return TraitDataEventHandler
     */
    public function addEventHandler(string $event, callable $handler, bool $exception = true): static
    {
        $this->getEventsObject()->add($handler, $event, true, $exception);
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


    /**
     * Returns the event for a specified key
     *
     * @param string      $event
     * @param string|null $in_script
     *
     * @return array
     */
    public function getEventHandler(string $event, ?string $in_script = null): mixed
    {
        if (array_key_exists($event, $this->event_handlers)) {
            $handler = $this->event_handlers[$event]['event'];

            if ($this->event_handlers[$event]['clear']) {
                $this->clearEventHandler($event);
            }

            if (is_callable($handler)) {
                $handler = $handler();
            }

            if ($in_script) {
                $handler = str_replace(':SCRIPT', $handler, $in_script);
            }

            return $handler;
        }

        return null;
    }


    /**
     * Sets the handler for the specified event type
     *
     * @param array|string $events
     * @param mixed        $handler
     * @param bool         $clear_after_execute
     *
     * @return static
     */
    public function setEventHandler(array|string $events, mixed $handler, bool $clear_after_execute = false): static
    {
        $events = Arrays::force($events);

        switch (count($events)) {
            case 0:
                break;

            case 1:
                $event = array_pop($events);

                $this->o_events->checkIsAllowed($event);

                if (array_key_exists($event, $this->event_handlers)) {
                    throw EventException::new(ts('The specified event ":event" already exists for class ":class"', [
                        ':event' => $event,
                        ':class' => static::class,
                    ]));
                }

                if (is_callable($handler)) {
                    $handler = $handler($this);
                }

                $this->event_handlers[$event]['event'] = $handler;
                $this->event_handlers[$event]['clear'] = $clear_after_execute;
                break;

            default:
                // Set this handler for multiple events
                foreach ($events as $event) {
                    $this->setEventHandler($event, $handler, $clear_after_execute);
                }
        }


        return $this;
    }
}
