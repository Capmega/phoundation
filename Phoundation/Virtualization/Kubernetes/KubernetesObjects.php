<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes;

use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\Classes\Iterator;
use Phoundation\Data\Traits\UsesNew;
use Phoundation\Processes\Process;
use Phoundation\Virtualization\Kubernetes\Traits\UsesKubeCtl;

/**
 * Class KubernetesObjects
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class KubernetesObjects extends Iterator
{
    use UsesKubeCtl;
    use UsesNew;

    /**
     * KubernetesObjects class constructor
     *
     * Gets the object list from kubectl right away and stores it in the internal list
     */
    public function __construct()
    {
        $format            = [];
        $this->list        = [];
        $this->kind        = Strings::fromReverse(get_class($this), '\\');
        $this->get_command = strtolower($this->kind);

        $output = Process::new('kubectl')
            ->addArguments(['get', $this->get_command])
            ->executeReturnArray();

        foreach ($output as $id => $line) {
            if (str_contains($line, 'No resources found in default namespace.')) {
                // There are no resources available
                break;
            }

            if (!$id) {
                // This is the header line, get the formatting information there
                $format = Arrays::detectFormat($line);
                continue;
            }

            $this->list[] = Arrays::format($line, $format);
        }
    }
}