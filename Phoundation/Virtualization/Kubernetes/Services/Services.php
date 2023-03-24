<?php

namespace Phoundation\Virtualization\Kubernetes\Services;

use Phoundation\Virtualization\Kubernetes\KubernetesObjects;


/**
 * Class Services
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class Services extends KubernetesObjects
{
    /**
     * Secrets class constructor
     */
    public function __construct()
    {
        $this->kind        = 'Services';
        $this->get_command = 'services';
        parent::__construct();
    }
}