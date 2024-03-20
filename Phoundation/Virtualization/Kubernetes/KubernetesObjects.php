<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitNew;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Virtualization\Kubernetes\Traits\TraitUsesKubeCtl;


/**
 * Class KubernetesObjects
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class KubernetesObjects extends Iterator
{
    use TraitUsesKubeCtl;


    /**
     * KubernetesObjects class constructor
     *
     * Gets the object list from kubectl right away and stores it in the internal list
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        $this->__construct($source);

        $format            = [];
        $this->source      = [];
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

            $this->source[] = Arrays::format($line, $format);
        }
    }
}