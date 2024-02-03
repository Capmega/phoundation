<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryComments
 *
 * This trait contains methods for DataEntry objects that require a name and comments
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryComments
{
    /**
     * Returns the comments for this object
     *
     * @return string|null
     */
    public function getComments(): ?string
    {
        return $this->getSourceColumnValue('string', 'comments');
    }


    /**
     * Sets the comments for this object
     *
     * @param string|null $comments
     * @return static
     */
    public function setComments(?string $comments): static
    {
        return $this->setSourceValue('comments', $comments);
    }
}
