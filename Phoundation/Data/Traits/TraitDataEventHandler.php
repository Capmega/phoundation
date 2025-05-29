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
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Data\Exception\EventException;

trait TraitDataEventHandler
{
    /**
     * The data for this object
     *
     * Looks like: ['key'] => ['event', 'clear_after_run']
     *
     * @var array $event_handlers
     */
    protected array $event_handlers = [];


    /**
     * Returns the event for a specified key
     *
     * @param string $key
     *
     * @return array
     */
    public function getEventHandler(string $key): mixed
    {
        if (array_key_exists($key, $this->event_handlers)) {
            $event = $this->event_handlers[$key]['event'];

            if ($this->event_handlers[$key]['clear']) {
                $this->clearEventHandler($key);
            }

            if (is_callable($event)) {
                $event = $event();
            }

            return $event;
        }

        return null;
    }


    /**
     * Sets the data
     *
     * @param string $key
     * @param mixed  $value
     * @param bool   $clear_after_execute
     *
     * @return static
     */
    public function setEventHandler(string $key, mixed $value, bool $clear_after_execute = false): static
    {
        if (array_key_exists($key, $this->event_handlers)) {
            throw EventException::new(ts('The specified event ":event" already exists for class ":class"', [
                ':event' => $key,
                ':class' => static::class,
            ]));
        }

        if (is_callable($value)) {
            $value = $value();
        }

        $this->event_handlers[$key]['event'] = $value;
        $this->event_handlers[$key]['clear'] = $clear_after_execute;

        return $this;
    }


   /**
     * Clears a certain key in the events array
     *
     * @param string $key
     *
     * @return static
     */
    public function clearEventHandler(string $key): static
    {
        unset($this->event_handlers[$key]);
        return $this;
    }
}
