<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Enums;

/**
 * Enum Services
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */
enum Services: string
{
    case minikube = 'minikube';
}
