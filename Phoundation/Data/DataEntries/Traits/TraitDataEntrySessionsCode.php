<?php

/**
 * Trait TraitDataEntrySessionsCode
 *
 * This trait contains methods for DataEntry objects that require a sessions_code field
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntrySessionsCode
{
    /**
     * Returns the session code for this object
     *
     * @return string|int|null
     */
    public function getSessionsCode(): string|int|null
    {
        return $this->getTypesafe('string', 'sessions_code');
    }


    /**
     * Sets the session code for this object
     *
     * @param string|int|null $code
     *
     * @return static
     */
    public function setSessionsCode(string|int|null $code): static
    {
        return $this->set(get_null($code), 'sessions_code');
    }
}
