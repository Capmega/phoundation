<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait TraitDataEntryMessage
 *
 * This trait contains methods for DataEntry objects that require a message
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryMessage
{
    /**
     * Returns the message for this object
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->getValueTypesafe('string', 'message');
    }


    /**
     * Sets the message for this object
     *
     * @param string|null $message
     *
     * @return static
     */
    public function setMessage(?string $message): static
    {
        if (strlen((string)$message) > 65536) {
            throw new OutOfBoundsException(tr('Specified message length ":length" is invalid, it should be 65536 characters or less', [
                ':length' => strlen($message),
            ]));
        }

        return $this->setValue('message', $message);
    }
}
