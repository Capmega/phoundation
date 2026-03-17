<?php

/**
 * Trait TraitDataEntryExpression
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryExpression
{
    /**
     * Returns the expression for this object
     *
     * @return string|null
     */
    public function getExpression(): ?string
    {
        return $this->getTypesafe('string', 'expression');
    }


    /**
     * Sets the expression for this object
     *
     * @param string|null $expression
     *
     * @return static
     */
    public function setExpression(?string $expression): static
    {
        return $this->set(get_null($expression), 'expression');
    }
}
