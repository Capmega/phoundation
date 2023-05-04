<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\ConfigMaps;

use Phoundation\Virtualization\Kubernetes\KubernetesObject;

/**
 * Class ConfigMap
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class ConfigMap extends KubernetesObject
{
    /**
     * ConfigMap class constructor
     *
     * Gets the object list from kubectl right away and stores it in the internal list
     */
    public function __construct(?string $name = null)
    {
        $this->object_file_class = ConfigMapFile::class;
        parent::__construct($name);
    }
}