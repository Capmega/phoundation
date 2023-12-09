<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Log\Log;
use Phoundation\Seo\Seo;


/**
 * Trait DataEntryTarget
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryTargetString
{
    /**
     * Returns the target for this object
     *
     * @return string|null
     */
    public function getTargetString(): ?string
    {
        return $this->getSourceFieldValue('string', 'target');
    }


    /**
     * Sets the target for this object
     *
     * @param string|null $target
     * @return static
     */
    public function setTargetString(?string $target): static
    {
        return $this->setSourceValue('target', $target);
    }
}
