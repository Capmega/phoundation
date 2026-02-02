<?php

/**
 * Class Events
 *
 * This class adds event handling support to your classes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Events 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils;

use PDOStatement;
use Phoundation\Data\Exception\EventException;
use Phoundation\Data\Exception\EventNotAllowedException;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\IteratorCore;
use Phoundation\Utils\Interfaces\EventsInterface;
use ReturnTypeWillChange;
use Stringable;
use TypeError;


class Events extends IteratorCore implements EventsInterface
{
    /**
     * Tracks the event keys that are allowed for the current object
     *
     * @var array $allowed_events
     */
    protected array $allowed_events = [];


    /**
     * Events class constructor
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        parent::__construct($source);
        $this->setAcceptedDataTypes('closure');
    }


    /**
     * Triggers the callback for the specified events
     *
     * @param Stringable|string|float|int $event                          The event key for which the callback should be executed
     * @param mixed                       $values                         The values to pass along to the callback function
     * @param bool                        $delete_after_execution [false] If true, this Events object will delete this event once it has been executed
     * @param bool                        $exception              [false] If true, will throw a NotExistsException if the specified event does not exist
     *
     * @return mixed                                                      The return value from the event callback, if available. NULL otherwise
     */
    public function trigger(Stringable|string|float|int $event, mixed $values, bool $delete_after_execution = false, bool $exception = false): mixed
    {
        // Is this event allowed?
        $this->checkIsAllowed($event);

        // Do we have a callback for this event?
        $callback = $this->get($event, exception: $exception);

        if ($callback) {
            $return = $callback($values);

            if ($delete_after_execution) {
                unset($this->source[$event]);
            }

            return $return;
        }

        return null;
    }


    /**
     * @inheritDoc
     */
    public function current(): callable
    {
        return parent::current();
    }


    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?callable
    {
        return parent::get($key, $default, $exception);
    }


    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function getRandom(): callable
    {
        return parent::getRandom();
    }


    /**
     * Returns the list of allowed events for this object
     *
     * @return array
     */
    public function getAllowed(): array
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
    protected function setAllowed(array $events): static
    {
        $this->allowed_events = $events;
        return $this;
    }


    /**
     * Adds a new allowed event for this object
     *
     * @param IteratorInterface|array|string $events
     *
     * @return static
     */
    protected function addAllowed(IteratorInterface|array|string $events): static
    {
        foreach ($events as $event) {
            try {
                $this->addAllowed($event);

            } catch (TypeError $e) {
                throw new EventException(tr('Cannot allow event ":event", it must be a string but is a ":type"', [
                    ':event' => $event,
                    ':type'  => gettype($event)
                ]), $e);
            }
        }

        return $this;
    }


    /**
     * Clears the list of allowed events for this object
     *
     * @return static
     */
    protected function clearAllowed(): static
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
    protected function isAllowed(string $event): bool
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
    protected function checkIsAllowed(string $event): static
    {
        if (!$this->isAllowed($event)) {
            throw new EventNotAllowedException(tr('The event key ":key" is not allowed for this ":class" class object', [
                ':key'   => $event,
                ':class' => static::class,
            ]));
        }

        return $this;
    }
}
