<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntrySubject
 *
 * This trait contains methods for DataEntry objects that require a subject
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntrySubject
{
    /**
     * Returns the subject for this object
     *
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->getSourceValue('string', 'subject');
    }


    /**
     * Sets the subject for this object
     *
     * @param string|null $number
     * @return static
     */
    public function setSubject(?string $number): static
    {
        return $this->setSourceValue('subject', $number);
    }
}
