<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Services;

use Phoundation\Virtualization\Kubernetes\KubernetesObject;
use Phoundation\Virtualization\Kubernetes\Traits\TraitDataSelectors;

/**
 * Class Service
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */
class Service extends KubernetesObject
{
    use TraitDataSelectors;

    /**
     * Service class constructor
     */
    public function __construct(?string $name = null)
    {
        $this->object_file_class = ServiceFile::class;
        parent::__construct($name);
    }
}