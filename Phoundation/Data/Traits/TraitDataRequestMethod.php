<?php

/**
 * Trait TraitDataRequestMethod
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;


trait TraitDataRequestMethod
{
    /**
     * The submit method
     *
     * @var EnumHttpRequestMethod|null $request_method
     */
    protected ?EnumHttpRequestMethod $request_method = null;


    /**
     * Returns the form request method
     *
     * @return EnumHttpRequestMethod|null
     */
    public function getRequestMethod(): ?EnumHttpRequestMethod
    {
        return $this->request_method;
    }


    /**
     * Sets the form request method
     *
     * @param EnumHttpRequestMethod $request_method
     *
     * @return static
     */
    public function setRequestMethod(EnumHttpRequestMethod $request_method): static
    {
        $this->request_method = $request_method;

        return $this;
    }
}
