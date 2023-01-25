<?php

namespace Phoundation\Data\DataEntry;

use Phoundation\Exception\Exception;
use Throwable;


/**
 * Trait DataEntryException
 *
 * This trait contains methods for DataEntry objects that require a exception
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryException
{
    /**
     * Returns the exception for this object
     *
     * @return string|null
     */
    public function getException(): ?string
    {
        return Exception::import($this->getDataValue('exception'));
    }



    /**
     * Sets the exception for this object
     *
     * @param Throwable|string|null $exception
     * @return static
     */
    public function setException(Throwable|string|null $exception): static
    {
        if (is_object($exception)) {
            if ($exception instanceof Exception) {
                // Make it a base Exception
                $exception = new Exception($exception);
            }

            $exception = $exception->export();
        }

        return $this->setDataValue('exception', $exception);
    }
}