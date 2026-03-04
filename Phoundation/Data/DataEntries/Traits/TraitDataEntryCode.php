<?php

/**
 * Trait TraitDataEntryCode
 *
 * This trait contains methods for DataEntry objects that require a code
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryCode
{
    /**
     * Returns the code for this object
     *
     * @return string|int|null
     */
    public function getCode(): string|int|null
    {
        return $this->getTypesafe('string|int', 'code');
    }


    /**
     * Returns the code for this object
     *
     * @return string|null
     */
    public function getDisplayCode(): string|null
    {
        return $this->formatDisplayVariables($this->getCode());
    }


    /**
     * Sets the code for this object
     *
     * @param string|int|null $code
     *
     * @return static
     */
    public function setCode(string|int|null $code): static
    {
        return $this->set(get_null($code), 'code');
    }
}
