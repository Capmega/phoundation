<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Ingresses;

use Phoundation\Exception\UnderConstructionException;
use Phoundation\Virtualization\Kubernetes\ObjectFile;


/**
 * Class IngressFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 *
 * @example
    apiVersion: networking.k8s.io/v1
    kind: Ingress
    metadata:
      name: minimal-ingress
      annotations:
        nginx.ingress.kubernetes.io/rewrite-target: /
    spec:
      ingressClassName: nginx-example
      rules:
      - http:
          paths:
          - path: /testpath
            pathType: Prefix
            backend:
              service:
                name: test
                port:
                  number: 80
 *
 */
class IngressFile extends ObjectFile
{
    /**
     * IngressFile class constructor
     */
    public function __construct(Ingress $ingress)
    {
        parent::__construct($ingress);
    }


    /**
     * Returns the kubernetes ingress data object for this ingress file
     *
     * @return Ingress
     */
    public function getIngress(): Ingress
    {
        return $this->object;
    }


    /**
     * Builds the data string for this ingress file from the Ingress object
     *
     * @param array|null $configuration
     * @return array
     */
    protected function renderConfiguration(?array $configuration = null): array
    {
throw new UnderConstructionException();
        return parent::renderConfiguration([
            'spec' => [
                'selector' => [
                    'matchLabels' => [
                        'app' => 'web'
                    ]
                ],
                'replicas' => $this->object->getReplicas(),
                'strategy' => [
                    'type' => 'RollingUpdate'
                ],
                'template' => [
                    'metadata' => [
                        'labels' => [
                            'app' => 'web'
                        ]
                    ],
                    'spec' => [
                        'containers' => [
                            [
                                'name' => 'nginx',
                                'image' => $this->object->getImage(),
                                'resources' => [
                                    'limits' => [
                                        'memory' => '200Mi',
                                    ],
                                    'requests' => [
                                        'cpu' => '100m',
                                        'memory' => '200Mi',
                                    ]
                                ],
                                'ports' => [
                                    [
                                        'containerPort' => 80
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }
}