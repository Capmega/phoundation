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
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\IteratorCore;
use Phoundation\Utils\Interfaces\EventsInterface;
use ReturnTypeWillChange;
use Stringable;


class Events extends IteratorCore implements EventsInterface
{
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
     * @param Stringable|string|float|int $event     The event key for which the callback should be executed
     * @param mixed                       $values    The values to pass along to the callback function
     * @param bool                        $exception If true, will throw a NotExistsException if the specified event
     *                                               does not exist
     * @return mixed                                 The return value from the event callback, if available. NULL
     *                                               otherwise
     */
    public function trigger(Stringable|string|float|int $event, mixed $values, bool $exception = false): mixed
    {
        $callback = $this->get($event, exception: $exception);

        if ($callback) {
            return $callback($values);
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
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): callable
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
}
