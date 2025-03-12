<?php

/**
 * Trait TraitDataBooleanException
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


trait TraitDataBooleanException
{
    /**
     * The exception flag for this object
     *
     * @var bool $exception
     */
    protected bool $exception = false;


    /**
     * Returns the exception flag
     *
     * @return bool
     */
    public function getException(): bool
    {
        return $this->exception;
    }


    /**
     * Sets the exception flag
     *
     * @param bool $exception
     *
     * @return static
     */
    public function setException(bool $exception): static
    {
        $this->exception = $exception;
        return $this;
    }
}
