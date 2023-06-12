<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Traits;

/**
 * Class KubeCtl
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
trait UsesKubeCtl
{
    /**
     * The kubernetes API version used
     *
     * @var string $api_version
     */
    protected string $api_version = 'v1';

    /**
     * The kind of kubernetes object
     *
     * @var string $kind
     */
    protected string $kind;

    /**
     * The command to get the data from kubectl
     *
     * @var string $get_command
     */
    protected string $get_command;

    /**
     * Returns the kind of Kubernetes object
     *
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }


    /**
     * Returns the API version for this object
     *
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->api_version;
    }


    /**
     * Returns the get command for use with kubectl
     *
     * @return string
     */
    public function getCommand(): string
    {
        return $this->get_command;
    }
}