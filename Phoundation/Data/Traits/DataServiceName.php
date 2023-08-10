<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Http\Html\Html;


/**
 * Trait DataService
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataServiceName
{
    /**
     * The service_name for this object
     *
     * @var string|null $service_name
     */
    protected ?string $service_name = null;


    /**
     * Returns the service name
     *
     * @return string|null
     */
    public function getServiceName(): ?string
    {
        return $this->service_name;
    }


    /**
     * Sets the service name
     *
     * @param string|null $service_name
     * @param bool $make_safe
     * @return static
     */
    public function setServiceName(?string $service_name, bool $make_safe = true): static
    {
        if ($make_safe) {
            $this->service_name = Html::safe($service_name);

        } else {
            $this->service_name = $service_name;
        }

        return $this;
    }
}