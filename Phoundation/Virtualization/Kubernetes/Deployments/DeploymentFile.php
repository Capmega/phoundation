<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Deployments;

use Phoundation\Virtualization\Kubernetes\ObjectFile;

/**
 * Class DeploymentFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 *
 * @example
 * apiVersion: apps/v1
 * kind: Deployment
 * metadata:
 * annotations:
 * deployment.kubernetes.io/revision: "1"
 * creationTimestamp: "2024-03-23T02:29:02Z"
 * generation: 1
 * labels:
 * app: phoundation
 * name: phoundation
 * namespace: default
 * resourceVersion: "97635"
 * uid: ec340b2c-8000-4d5e-ab55-f83fec862654
 * spec:
 * progressDeadlineSeconds: 600
 * replicas: 1
 * revisionHistoryLimit: 10
 * selector:
 * matchLabels:
 * app: mongo
 * strategy:
 * rollingUpdate:
 * maxSurge: 25%
 * maxUnavailable: 25%
 * type: RollingUpdate
 * template:
 * metadata:
 * creationTimestamp: null
 * labels:
 * app: mongo
 * spec:
 * containers:
 * - image: mongo
 * imagePullPolicy: Always
 * name: mongo
 * resources: {}
 * terminationMessagePath: /dev/termination-log
 * terminationMessagePolicy: FsFileFileInterface
 * dnsPolicy: ClusterFirst
 * restartPolicy: Always
 * schedulerName: default-scheduler
 * securityContext: {}
 * terminationGracePeriodSeconds: 30
 *
 */
class DeploymentFile extends ObjectFile
{
    /**
     * DeploymentFile class constructor
     */
    public function __construct(Deployment $deployment)
    {
        parent::__construct($deployment);
    }


    /**
     * Returns the kubernetes deployment data object for this deployment file
     *
     * @return Deployment
     */
    public function getDeployment(): Deployment
    {
        return $this->object;
    }


    /**
     * Builds the data string for this deployment file from the Deployment object
     *
     * @param array|null $configuration
     *
     * @return array
     */
    protected function renderConfiguration(?array $configuration = null): array
    {
        return parent::renderConfiguration([
            'spec' => [
                'selector' => [
                    'matchLabels' => [
                        'app' => 'web',
                    ],
                ],
                'replicas' => $this->object->getReplicas(),
                'strategy' => [
                    'type' => 'RollingUpdate',
                ],
                'template' => [
                    'metadata' => [
                        'labels' => [
                            'app' => 'web',
                        ],
                    ],
                    'spec'     => [
                        'containers' => [
                            [
                                'name'      => 'nginx',
                                'image'     => $this->object->getImage(),
                                'resources' => [
                                    'limits'   => [
                                        'memory' => '200Mi',
                                    ],
                                    'requests' => [
                                        'cpu'    => '100m',
                                        'memory' => '200Mi',
                                    ],
                                ],
                                'ports'     => [
                                    [
                                        'containerPort' => 80,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}