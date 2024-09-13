<?php

/**
 * Trait TraitDataEntryCode
 *
 * This trait contains methods for DataEntry objects that require a code
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryCode
{
    /**
     * Returns the code for this object
     *
     * @return string|int|null
     */
    public function getCode(): string|int|null
    {
        return $this->getTypesafe('string', 'code');
    }


    /**
     * Sets the code for this object
     *
     * @note This method prefixes each code with a "#" symbol to ensure that codes are never seen as numeric, which
     *       would cause issues with $identifier detection, as $identifier can be numeric (ALWAYS id) or non numeric
     *       (The other unique column)
     *
     * @param string|int|null $code
     *
     * @return static
     */
    public function setCode(string|int|null $code): static
    {
        return $this->set($code, 'code');
    }
}
