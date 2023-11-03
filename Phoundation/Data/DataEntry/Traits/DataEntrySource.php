<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Log\Log;
use Phoundation\Seo\Seo;


/**
 * Trait DataEntrySource
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntrySource
{
    /**
     * Returns the source for this object
     *
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->getSourceFieldValue('string', 'source');
    }


    /**
     * Sets the source for this object
     *
     * @param string|null $source
     * @return static
     */
    public function setSource(?string $source): static
    {
        return $this->setSourceValue('source', $source);
    }
}
