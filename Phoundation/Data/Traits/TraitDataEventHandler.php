<?php

/**
 * Trait TraitDataEvent
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 * @deprecated
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Data\Exception\EventException;
use Phoundation\Data\Exception\EventNotAllowedException;
use Phoundation\Data\Interfaces\IteratorInterface;
use TypeError;


trait TraitDataEventHandler
{
    // TODO DEPRECATED This event handler must be merged with the Utils\Traits\TraitEventHandler class

    /**
     * The data for this object
     *
     * Looks like: ['key'] => ['event', 'clear_after_run']
     *
     * @var array $event_handlers
     */
    protected array $event_handlers = [];

    /**
     * Tracks the event keys that are allowed for the current object
     *
     * @var array $allowed_events
     */
    protected array $allowed_events = [];


    /**
     * Returns the list of allowed events for this object
     *
     * @return array
     */
    public function getAllowedEvents(): array
    {
        return $this->allowed_events;
    }


    /**
     * Sets the list of allowed events for this object
     *
     * @param array $events
     *
     * @return static
     */
    protected function setAllowedEvents(array $events): static
    {
        $this->allowed_events = $events;
        return $this;
    }


    /**
     * Adds a new allowed event for this object
     *
     * @param IteratorInterface|array $events
     *
     * @return static
     */
    protected function addAllowedEvents(IteratorInterface|array $events): static
    {
        foreach ($events as $event) {
            try {
                $this->addAllowedEvent($event);

            } catch (TypeError) {
                throw new EventException(tr('Cannot allow event ":event", it must be a string but is a ":type"', [
                    ':event' => $event,
                    ':type'  => gettype($event)
                ]), $e);
            }
        }

        return $this;
    }


    /**
     * Adds a new allowed event for this object
     *
     * @param string $event
     *
     * @return static
     */
    protected function addAllowedEvent(string $event): static
    {
        $this->allowed_events[$event] = $event;
        return $this;
    }


    /**
     * Clears the list of allowed events for this object
     *
     * @return static
     */
    protected function clearAllowedEvents(): static
    {
        $this->allowed_events = [];
        return $this;
    }


    /**
     * Returns true if the specified even key is allowed
     *
     * @param string $event
     *
     * @return bool
     */
    protected function eventIsAllowed(string $event): bool
    {
        if (empty($this->allowed_events)) {
            return true;
        }

        return array_key_exists($event, $this->allowed_events);
    }


    /**
     * Checks if the specified event key is allowed and will throw an exception if not
     *
     * @param string $event
     *
     * @return static
     */
    protected function checkEventIsAllowed(string $event): static
    {
        if (!$this->eventIsAllowed($event)) {
            throw new EventNotAllowedException(tr('The event key ":key" is not allowed for this ":class" class object', [
                ':key'   => $event,
                ':class' => static::class,
            ]));
        }

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
                $this->clearEventHandlerDeprecated($event);
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
     * Adds a handler for the specified event type
     *
     * @note wrapper for static::setEventHandler()
     *
     * @param string $event
     * @param mixed  $handler
     * @param bool   $clear_after_execute
     *
     * @return static
     */
    public function addEventHandlerDeprecated(string $event, mixed $handler, bool $clear_after_execute = false): static
    {
        return $this->setEventHandlerDeprecated($event, $handler, $clear_after_execute);
    }


    /**
     * Sets the handler for the specified event type
     *
     * @param string $event
     * @param mixed  $handler
     * @param bool   $clear_after_execute
     *
     * @return static
     */
    public function setEventHandlerDeprecated(string $event, mixed $handler, bool $clear_after_execute = false): static
    {
        $this->checkEventIsAllowed($event);

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

        return $this;
    }


   /**
     * Clears the event handler for the specified event
     *
     * @param string $event
     *
     * @return static
     */
    public function clearEventHandlerDeprecated(string $event): static
    {
        unset($this->event_handlers[$event]);
        return $this;
    }


    /**
     * Returns a list of all defined event handlers
     *
     * @return array
     */
    public function getEventHandlersDeprecated(): array
    {
        return $this->event_handlers;
    }


    /**
     * Sets a list of all defined event handlers
     *
     * @param IteratorInterface|array|null $events
     *
     * @return TraitDataEventHandler
     */
    public function setEventHandlersDeprecated(IteratorInterface|array|null $events): static
    {
        $this->event_handlers = [];

        if ($events) {
            foreach ($events as $event => $handler) {
                $this->setEventHandlerDeprecated($event, $handler);
            }
        }

        return $this;
    }
}
