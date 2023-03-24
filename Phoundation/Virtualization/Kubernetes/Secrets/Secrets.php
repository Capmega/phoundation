<?php

namespace Phoundation\Virtualization\Kubernetes\Secrets;

use Phoundation\Virtualization\Kubernetes\KubernetesObjects;


/**
 * Class Secrets
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class Secrets extends KubernetesObjects
{
    /**
     * Secrets class constructor
     */
    public function __construct()
    {
        $this->kind        = 'Secret';
        $this->get_command = 'secret';
        parent::__construct();
    }
}