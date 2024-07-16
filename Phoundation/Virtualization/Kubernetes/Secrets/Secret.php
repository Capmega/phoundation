<?php

/**
 * Class Secret
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Secrets;

use Phoundation\Virtualization\Kubernetes\KubernetesObject;

class Secret extends KubernetesObject
{
    /**
     * Secret class constructor
     */
    public function __construct(?string $name = null)
    {
        $this->object_file_class = SecretFile::class;
        parent::__construct($name);
    }
}