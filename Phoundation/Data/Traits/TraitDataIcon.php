<?php

/**
 * Trait TraitDataIcon
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openicon.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Components\Icons\Interfaces\IconInterface;


trait TraitDataIcon
{
    /**
     * The icon to use
     *
     * @var IconInterface|null $icon
     */
    protected ?IconInterface $icon = null;


    /**
     * Returns the icon
     *
     * @return IconInterface|null
     */
    public function getIcon(): ?IconInterface
    {
        return $this->icon;
    }


    /**
     * Sets the icon
     *
     * @param IconInterface|null $icon
     *
     * @return static
     */
    public function setIcon(?IconInterface $icon): static
    {
        $this->icon = $icon;

        return $this;
    }
}
