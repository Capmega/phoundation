<?php

declare(strict_types=1);

namespace Phoundation\Web\Requests\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Requests\Routing\Interfaces\RoutingParametersInterface;


/**
 * Trait TraitDataStaticParameters
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Developer
 */
trait TraitDataStaticRouteParameters
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var RoutingParametersInterface $parameters
     */
    protected static RoutingParametersInterface $parameters;


    /**
     * Returns the routing parameters
     *
     * @return RoutingParametersInterface
     * @throws OutOfBoundsException Thrown when routing parameters have not yet been set
     */
    public static function getParameters(): RoutingParametersInterface
    {
        if (static::$parameters) {
            return static::$parameters;
        }

        throw new OutOfBoundsException(tr('Cannot return routing parameters, parameters have not yet been set'));
    }


    /**
     * Sets the routing parameters
     *
     * @param RoutingParametersInterface $parameters
     * @return void
     */
    public static function setParameters(RoutingParametersInterface $parameters): void
    {
        static::$parameters = $parameters;
    }
}
