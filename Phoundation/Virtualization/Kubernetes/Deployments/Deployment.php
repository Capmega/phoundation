<?php

namespace Phoundation\Virtualization\Kubernetes\Deployments;

use Phoundation\Data\Traits\DataName;
use Phoundation\Virtualization\Kubernetes\KubernetesObject;
use Phoundation\Virtualization\Kubernetes\Traits\DataReplicas;
use Phoundation\Virtualization\Traits\DataImage;


/**
 * Class Deployment
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class Deployment extends KubernetesObject
{
    use DataName;
    use DataImage;
    use DataReplicas;


    /**
     * Deployment class constructor
     *
     * Gets the object list from kubectl right away and stores it in the internal list
     */
    public function __construct(?string $name = null)
    {
        $this->object_file_class = DeploymentFile::class;
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