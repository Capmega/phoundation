<?php

namespace Phoundation\Virtualization\Kubernetes\Pods;

use Phoundation\Data\Classes\Iterator;
use Phoundation\Processes\Process;


/**
 * Class Pod
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class Pods extends Iterator
{
    /**
     * Pods class constructor
     */
    public function __construct()
    {
        $this->list = [];

        $output = Process::new('kubectl')
            ->addArguments(['get', 'pods'])
            ->executeReturnArray();

        foreach ($output as $id => $line) {
            if (!$id) {
                // This is the header line
                continue;
            }

            $this->list[] = explode(' ', $line);
        }
    }


    /**
     * Pod class constructor
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }
}