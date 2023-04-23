<?php

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait DataEntryIcon
 *
 * This trait contains methods for DataEntry objects that require a icon
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryIcon
{
    /**
     * Returns the icon for this object
     *
     * @return string|null
     */
    public function getIcon(): ?string
    {
        $return = $this->getDataValue('icon');

        if (!$return) {
            // Assign default icon
            return match (strtolower($this->getMode())) {
                'warning', 'danger' => 'exclamation-circle',
                'success'           => 'check-circle',
                'info'              => 'info-circle',
                default             => 'question-circle',
            };
        }

        return $return;
    }


    /**
     * Sets the icon for this object
     *
     * @param string|null $icon
     * @return static
     */
    public function setIcon(?string $icon): static
    {
        if (strlen($icon) > 32) {
            throw new OutOfBoundsException(tr('Specified icon ":icon" is invalid, the string should be no longer than 32 characters', [
                ':icon' => $icon
            ]));
        }

        return $this->setDataValue('icon', $icon);
    }
}

