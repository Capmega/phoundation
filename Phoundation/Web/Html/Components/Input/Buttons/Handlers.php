<?php

/**
 * Class Handlers
 *
 * This class manages callback handlers for specific pressed buttons
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons;

use PDOStatement;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Exception\NotExistsException;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\HandlersInterface;
use ReturnTypeWillChange;
use Stringable;


class Handlers extends Iterator implements HandlersInterface
{
    /**
     * Handlers class constructor
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source An array or Iterator containing the handlers for the various buttons (optional)
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null) {
        parent::__construct($source);
        $this->setAcceptedDataTypes('closure');
    }


    /**
     * Returns the specified Package object
     *
     * @param Stringable|string|float|int $key              The key that should return the correct value
     * @param mixed|null                  $default   [null] The default value to return if the specified key does not exist
     * @param bool|null                   $exception [true] If true, will throw a NotExistsException instead of returning the default value
     *
     * @return callable|null
     * @throws NotExistsException
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = true): ?callable
    {
        try {
           return parent::get($key, $default, $exception);

        } catch (NotExistsException $e) {
            throw new NotExistsException(ts('The specified button events handler ":button" does not exist', [
                ':button' => $key
            ]), $e);
        }
    }


    /**
     * Returns a random Package
     *
     * @return callable|null
     */
    #[ReturnTypeWillChange] public function getRandom(): ?callable
    {
        return parent::getRandom();
    }


    /**
     * Returns the current Package
     *
     * @note overrides the IteratorCore::current() method which returns mixed
     *
     * @return callable|null
     */
    #[ReturnTypeWillChange] public function current(): ?callable
    {
        return parent::current();
    }
}
