<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Services;

use Phoundation\Virtualization\Kubernetes\KubernetesObject;
use Phoundation\Virtualization\Kubernetes\ObjectFile;


/**
 * Class ServiceFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 *
 * @example
    apiVersion: v1
    kind: Service
    metadata:
      name: my-service
    spec:
      selector:
        app.kubernetes.io/name: MyApp
      ports:
        - protocol: TCP
          port: 80
          targetPort: 9376
 *
 */
class ServiceFile extends ObjectFile
{
    /**
     * ServiceFile class constructor
     */
    public function __construct(Service $service)
    {
        parent::__construct($service);
    }


    /**
     * Returns the deployment data object for this deployment file
     *
     * @return Service
     */
    public function getService(): Service
    {
        return $this->object;
    }


    /**
     * Builds the data string for this deployment file from the Service object
     *
     * @param array|null $configuration
     * @return array
     */
    protected function buildConfiguration(?array $configuration = null): array
    {
        return parent::buildConfiguration([
            'spec' => [
                'selector' => $this->object->getSelectors(),
                'ports' => [
                    [
                        'protocol'   => 'TCP',
                        'port'       => 80,
                        'targetPort' => 9376,
                    ]
                ]
            ]
        ]);
    }
}