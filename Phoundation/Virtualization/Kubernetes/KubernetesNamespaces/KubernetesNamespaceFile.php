<?php

namespace Phoundation\Virtualization\Kubernetes\KubernetesNamespaces;

use Phoundation\Virtualization\Kubernetes\ObjectFile;

/**
 * Class KubernetesNamespaceFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 *
 * @example
    apiVersion: apps/v1
    kind: KubernetesNamespace
    metadata:
      annotations:
        deployment.kubernetes.io/revision: "1"
      creationTimestamp: "2023-03-23T02:29:02Z"
      generation: 1
      labels:
        app: phoundation
      name: phoundation
      namespace: default
      resourceVersion: "97635"
      uid: ec340b2c-8000-4d5e-ab55-f83fec862654
    spec:
      progressDeadlineSeconds: 600
      replicas: 1
      revisionHistoryLimit: 10
      selector:
        matchLabels:
          app: mongo
      strategy:
        rollingUpdate:
          maxSurge: 25%
      maxUnavailable: 25%
      type: RollingUpdate
      template:
        metadata:
          creationTimestamp: null
          labels:
            app: mongo
        spec:
          containers:
          - image: mongo
            imagePullPolicy: Always
            name: mongo
            resources: {}
            terminationMessagePath: /dev/termination-log
            terminationMessagePolicy: File
          dnsPolicy: ClusterFirst
          restartPolicy: Always
          schedulerName: default-scheduler
          securityContext: {}
          terminationGracePeriodSeconds: 30
 *
 */
class KubernetesNamespaceFile extends ObjectFile
{
    /**
     * KubernetesNamespaceFile class constructor
     */
    public function __construct(KubernetesNamespace $deployment)
    {
        parent::__construct($deployment);
    }


    /**
     * Returns the kubernetes deployment data object for this deployment file
     *
     * @return KubernetesNamespace
     */
    public function getKubernetesNamespace(): KubernetesNamespace
    {
        return $this->object;
    }


    /**
     * Builds the data string for this deployment file from the KubernetesNamespace object
     *
     * @param array|null $configuration
     * @return array
     */
    protected function buildConfiguration(?array $configuration = null): array
    {
        return parent::buildConfiguration([
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