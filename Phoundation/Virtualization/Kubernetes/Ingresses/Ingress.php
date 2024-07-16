<?php

/**
 * Class Ingress
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Ingresses;

use Phoundation\Virtualization\Kubernetes\KubernetesObject;

class Ingress extends KubernetesObject
{
    /**
     * Ingress class constructor
     *
     * Gets the object list from kubectl right away and stores it in the internal list
     */
    public function __construct(?string $name = null)
    {
        $this->object_file_class = IngressFile::class;
        parent::__construct($name);
    }


    /**
     * Returns the API version for this object
     *
     * @return string
     */
    public function getApiVersion(): string
    {
        return 'networking.k8s.io/' . $this->api_version;
    }
}