<?php

/**
 * Trait TraitDataEntryException
 *
 * This trait contains methods for DataEntry objects that require a exception
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\Exception;
use Throwable;


trait TraitDataEntryException
{
    /**
     * Returns the exception for this object
     *
     * @return Exception|null
     */
    public function getException(): ?Exception
    {
        return Exception::newFromImport($this->getTypesafe('string', 'exception'));
    }


    /**
     * Sets the exception for this object
     *
     * @param Throwable|string|null $e
     *
     * @return static
     */
    public function setException(Throwable|string|null $e): static
    {
        if (is_object($e)) {
            if ($e instanceof Exception) {
                // Make it a Phoundation Exception
                $e = new Exception($e);
            }

            $e = $e->exportToString();
        }

        return $this->set($e, 'exception');
    }
}
