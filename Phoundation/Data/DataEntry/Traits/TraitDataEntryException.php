<?php

/**
 * Trait TraitDataEntryException
 *
 * This trait contains methods for DataEntry objects that require a exception
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\PhoException;
use Throwable;


trait TraitDataEntryException
{
    /**
     * Returns the exception for this object
     *
     * @return PhoException|null
     */
    public function getException(): ?PhoException
    {
        return PhoException::newFromImport($this->getTypesafe('string', 'exception'));
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
            if (!$e instanceof PhoException) {
                // Make it a Phoundation Exception
                $e = new PhoException($e);
            }

            $e = $e->exportToString();
        }

        return $this->set(get_null($e), 'exception');
    }
}
