<?php

/**
 * Class TraitDataExceptionOnGet
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


trait TraitDataExceptionOnGet
{
    /**
     * Tracks whether Iterator::get() calls should by default cause an exception when the requested key does not exist
     *
     * @var bool $exception_on_get
     */
    protected bool $exception_on_get = false;


    /**
     * Returns whether Iterator::get() calls should by default cause an exception when the requested key does not exist
     *
     * @return bool
     */
    public function getExceptionOnGet(): bool
    {
        return $this->exception_on_get;
    }


    /**
     * Returns whether Iterator::get() calls should by default cause an exception when the requested key does not exist
     *
     * @param bool $exception
     *
     * @return $this
     */
    public function setExceptionOnGet(bool $exception): static
    {
        $this->exception_on_get = $exception;
        return $this;
    }
}
