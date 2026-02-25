<?php

/**
 * Trait TraitDataStaticParameters
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Routing\Interfaces\RoutingParametersInterface;


trait TraitDataStaticRouteParameters
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var RoutingParametersInterface $_parameters
     */
    protected static RoutingParametersInterface $_parameters;


    /**
     * Returns the routing parameters
     *
     * @return RoutingParametersInterface
     * @throws OutOfBoundsException Thrown when routing parameters have not yet been set
     */
    public static function getParametersObject(): RoutingParametersInterface
    {
        if (static::$_parameters) {
            return static::$_parameters;
        }
        throw new OutOfBoundsException(tr('Cannot return routing parameters, parameters have not yet been set'));
    }


    /**
     * Sets the routing parameters
     *
     * @param RoutingParametersInterface $_parameters
     *
     * @return void
     */
    public static function setParametersObject(RoutingParametersInterface $_parameters): void
    {
        static::$_parameters = $_parameters;
    }
}
