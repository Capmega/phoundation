<?php

/**
 * Trait TraitDataClassException
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

use Throwable;

trait TraitDataClassException
{
    /**
     * Object exception
     *
     * @var Throwable|null $exception
     */
    protected ?Throwable $exception = null;


    /**
     * Returns the Throwable exception for this object or null
     *
     * @return Throwable|null
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
    }


    /**
     * Sets the Throwable ID for this object
     *
     * @param Throwable|null $exception
     * @return static
     */
    public function setException(?Throwable $exception): static
    {
        $this->exception = $exception;
        return $this;
    }
}
