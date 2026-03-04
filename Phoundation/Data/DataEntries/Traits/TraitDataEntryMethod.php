<?php

/**
 * Trait TraitDataEntryMethod
 *
 * This trait contains methods for DataEntry objects that require a method
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;

trait TraitDataEntryMethod
{
    /**
     * Returns the method for this object
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->getTypesafe('string', 'method');
    }


    /**
     * Sets the method for this object
     *
     * @param EnumHttpRequestMethod|string|null $method
     *
     * @return static
     */
    public function setMethod(EnumHttpRequestMethod|string|null $method): static
    {
        if ($method) {
            if (is_object($method)) {
                $method = $method->value;

            } else {
                $method = EnumHttpRequestMethod::from($method)->value;
            }
        }

        return $this->set(get_null($method), 'method');
    }
}
