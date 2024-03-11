<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Pods;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Virtualization\Kubernetes\KubernetesObjects;


/**
 * Class Pods
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class Pods extends KubernetesObjects
{
    /**
     * Pods class constructor
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        $this->kind        = 'Pod';
        $this->get_command = 'pods';

        parent::__construct($source);
    }
}