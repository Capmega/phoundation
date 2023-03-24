<?php

namespace Phoundation\Virtualization\Kubernetes\Deployments;

use Phoundation\Virtualization\Kubernetes\KubernetesFile;


/**
 * Class DeploymentFile
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
    kind: Deployment
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

class DeploymentFile extends KubernetesFile
{
    /**
     * The deployment data object for this deployment file
     *
     * @var Deployment $deployment
     */
    protected Deployment $deployment;


    /**
     * DeploymentFile class constructor
     */
    public function __construct(Deployment $deployment)
    {
        $this->kind       = 'Deployment';
        $this->deployment = $deployment;

        parent::__construct();

        $this->file .= $this->deployment->getName() . '.yml';
        $this->load();
    }


    /**
     * Returns the deployment data object for this deployment file
     *
     * @return Deployment
     */
    public function getDeployment(): Deployment
    {
        return $this->deployment;
    }


    /**
     * Builds the data string for this deployment file from the Deployment object
     *
     * @param array|null $configuration
     * @return array
     */
    protected function buildConfiguration(?array $configuration = null): array
    {
//        'ports' => '—containerPort: 80'
//        '—name' => 'nginx',

        return parent::buildConfiguration([
            'metadata' => [
                'name' => $this->deployment->getName(),
                'labels' => [
                    'app' => 'web'
                ]
            ],
            'spec' => [
                'selector' => [
                    'matchLabels' => [
                        'app' => 'web'
                    ]
                ],
                'replicas' => $this->deployment->getReplicas(),
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
                                'image' => $this->deployment->getImage(),
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


    /**
     * Loads the deployment file data into the Deployment object
     *
     * @return void
     */
    protected function load(): void
    {
        if (file_exists($this->file)) {
            // Load and parse the file?
        }
    }
}