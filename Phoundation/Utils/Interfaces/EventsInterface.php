<?php

namespace Phoundation\Utils\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Stringable;


interface EventsInterface extends IteratorInterface
{
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
    public function trigger(Stringable|string|float|int $event, mixed $values, bool $delete_after_execution = false, bool $exception = false): mixed;

    /**
     * @inheritDoc
     */
    public function current(): callable;

    /**
     * @inheritDoc
     */
    public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?callable;

    /**
     * @inheritDoc
     */
    public function getRandom(): callable;
}
