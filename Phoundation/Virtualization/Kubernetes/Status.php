<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes;

use Phoundation\Core\Arrays;
use Phoundation\Exception\OutOfBoundsException;

class Status
{
    /**
     * The raw array data
     *
     * @var array $status
     */
    readonly array $status;

    /**
     * System running kubernetes cluster
     *
     * @var string $service
     */
    protected string $service;

    /**
     *
     *
     * @var string $type
     */
    protected string $type;

    /**
     * Keeps track of the host service status
     *
     * @var string $host
     */
    protected string $host;

    /**
     * Keeps track of the kubelet status
     *
     * @var string $kubelet
     */
    protected string $kubelet;

    /**
     * Keeps track of the API server status
     *
     * @var string $apiserver
     */
    protected string $apiserver;

    /**
     * Keeps track of the kubeconfig status
     *
     * @var string $kubeconfig
     */
    protected string $kubeconfig;

    /**
     * Status class constructor
     *
     * @param array|string $status
     */
    public function __construct(array|string $status)
    {
        /*
         * An example of the status string can be:
            minikube
            type: Control Plane
            host: Running
            kubelet: Running
            apiserver: Running
            kubeconfig: Configured
         */

        $status = Arrays::force($status);
        $this->status = $status;

        foreach ($status as $line) {
            if (!str_contains($line, ':')) {
                $this->service = $line;
                continue;
            }

            $data    = explode(':', $line);
            $data[1] = strtolower(isset_get($data[1]));

            switch ($data[0]) {
                case 'type':
                    $this->type = $data[1];
                    break;

                case 'host':
                    $this->host = $data[1];
                    break;

                case 'kubelet':
                    $this->kubelet = $data[1];
                    break;

                case 'apiserver':
                    $this->apiserver = $data[1];
                    break;

                case 'kubeconfig':
                    $this->kubeconfig = $data[1];
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown minikube output line ":line" encountered', [
                        ':line' => $line
                    ]));
            }
        }
    }


    /**
     * Returns the service that manages the kubernetes cluster
     *
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }


    /**
     * Returns the service type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }


    /**
     * Returns the host status
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }


    /**
     * Returns the kubelet status
     *
     * @return string
     */
    public function getKubelet(): string
    {
        return $this->kubelet;
    }


    /**
     * Returns the API server status
     *
     * @return string
     */
    public function getApiServer(): string
    {
        return $this->apiserver;
    }


    /**
     * Returns the KubeConfig status
     *
     * @return string
     */
    public function getKubeConfig(): string
    {
        return $this->kubeconfig;
    }
}