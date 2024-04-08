<?php

declare(strict_types=1);

namespace Phoundation\Web;

use Phoundation\Utils\Config;
use Phoundation\Web\Html\Enums\EnumContainerTier;
use Phoundation\Web\Html\Enums\Interfaces\EnumContainerTierInterface;
use Phoundation\Web\Interfaces\BootstrapInterface;

/**
 * Class Bootstrap
 *
 * This class manages anything bootstrap related
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * Returns the default configured bootstrap grid container tier
     *
     * @param EnumContainerTierInterface|string|null $default_to
     *
     * @return EnumContainerTierInterface
     */
    public static function getGridContainerTier(EnumContainerTierInterface|string|null $default_to = null): EnumContainerTierInterface
    {
        if ($default_to instanceof EnumContainerTierInterface) {
            $default_to = $default_to->value;
        }

        return EnumContainerTier::from(Config::getString('web.bootstrap.grid.container-tier', $default_to));
    }
}
