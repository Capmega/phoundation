<?php

namespace Phoundation\Web;

use Phoundation\Utils\Config;
use Phoundation\Web\Html\Enums\ContainerTier;
use Phoundation\Web\Html\Enums\Interfaces\ContainerTierInterface;
use Phoundation\Web\Interfaces\BootstrapInterface;


/**
 * Class Bootstrap
 *
 * This class manages anything bootstrap related
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * Returns the default configured bootstrap grid container tier
     *
     * @param ContainerTierInterface|string|null $default_to
     * @return ContainerTierInterface
     */
    public static function getGridContainerTier(ContainerTierInterface|string|null $default_to = null): ContainerTierInterface
    {
        if ($default_to instanceof ContainerTierInterface) {
            $default_to = $default_to->value;
        }

        return ContainerTier::tryFrom(Config::getString('web.bootstrap.grid.container-tier', $default_to));
    }
}
