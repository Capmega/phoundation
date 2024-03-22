<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Icons\Icon;
use Phoundation\Web\Html\Components\Icons\Interfaces\IconInterface;


/**
 * Trait TraitDataEntryIcon
 *
 * This trait contains methods for DataEntry objects that require a icon
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataEntryIcon
{
    /**
     * Returns the icon for this object
     *
     * @return IconInterface|null
     */
    public function getIcon(): ?IconInterface
    {
        return Icon::new($this->getValueTypesafe('string', 'icon'));
//
//        if (!$return) {
//            // Assign default icon
//            $return = match ($this->getMode()) {
//                EnumDisplayMode::warning, EnumDisplayMode::danger => 'exclamation-circle',
//                EnumDisplayMode::success                          => 'check-circle',
//                EnumDisplayMode::info, EnumDisplayMode::notice    => 'info-circle',
//                default                                           => 'question-circle',
//            };
//        }
//
//        return Icon::new($return);
    }


    /**
     * Sets the icon for this object
     *
     * @param IconInterface|string|null $icon
     * @return static
     */
    public function setIcon(IconInterface|string|null $icon): static
    {
        if ($icon instanceof IconInterface) {
            $icon = $icon->getContent();
        }

        if (strlen((string) $icon) > 32) {
            throw new OutOfBoundsException(tr('Specified icon name ":icon" is invalid, the string should be no longer than 32 characters', [
                ':icon' => $icon
            ]));
        }

        return $this->setValue('icon', $icon);
    }
}
