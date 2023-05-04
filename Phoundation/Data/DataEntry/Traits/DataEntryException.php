<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

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
     * @return Exception|null
     */
    public function getException(): ?Exception
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

            $exception = $exception->exportString();
        }

        return $this->setDataValue('exception', $exception);
    }
}