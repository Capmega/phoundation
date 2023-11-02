<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryContent
 *
 * This trait contains methods for DataEntry objects that require a content
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryContent
{
    /**
     * Returns the content for this object
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->getSourceFieldValue('string', 'content');
    }


    /**
     * Sets the content for this object
     *
     * @param string|null $content
     * @return static
     */
    public function setContent(?string $content): static
    {
        return $this->setSourceValue('content', $content);
    }
}
