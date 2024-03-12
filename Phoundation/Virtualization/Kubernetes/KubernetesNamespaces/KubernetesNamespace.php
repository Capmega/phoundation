<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\KubernetesNamespaces;

use Phoundation\Data\Traits\TraitDataName;
use Phoundation\Virtualization\Kubernetes\KubernetesObject;
use Phoundation\Virtualization\Kubernetes\Traits\TraitDataReplicas;
use Phoundation\Virtualization\Traits\TraitDataImage;


/**
 * Class KubernetesNamespace
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class KubernetesNamespace extends KubernetesObject
{
    use TraitDataName;
    use TraitDataImage;
    use TraitDataReplicas;

    /**
     * Namespace class constructor
     *
     * Gets the object list from kubectl right away and stores it in the internal list
     */
    public function __construct(?string $name = null)
    {
        $this->object_file_class = KubernetesNamespaceFile::class;
        parent::__construct($name);
    }


    /**
     * Returns the API version for this object
     *
     * @return string
     */
    public function getApiVersion(): string
    {
        return 'apps/' . $this->api_version;
    }
}