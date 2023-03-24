<?php

namespace Phoundation\Virtualization\Kubernetes\Deployments;

use Phoundation\Core\Arrays;
use Phoundation\Data\Classes\Iterator;
use Phoundation\Processes\Process;


/**
 * Class Deployment
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class Deployments extends Iterator
{
    /**
     * Deployments class constructor
     *
     * format should be something like this:
     * [
        'name'       => 20,
        'ready'      => 8,
        'up-to-date' => 13,
        'available'  => 12,
        'age'        => 10,
     * ]
     */
    public function __construct()
    {
        $this->list = [];
        $format     = [];

        $output = Process::new('kubectl')
            ->addArguments(['get', 'deployments'])
            ->executeReturnArray();

        foreach ($output as $id => $line) {
            if (!$id) {
                // This is the header line
                $format = Arrays::detectFormat($line);
                continue;
            }

            $this->list[] = Arrays::format($line, $format);
        }
    }

    /**
     * Deployment class constructor
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }
}