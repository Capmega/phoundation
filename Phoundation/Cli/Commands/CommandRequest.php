<?php

namespace Phoundation\Cli\Commands;

use Phoundation\Cli\Commands\Interfaces\CommandRequestInterface;
use Phoundation\Core\Request;


/**
 * Class CommandRequest
 *
 *
 *
 * @author Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license This plugin is developed by, and may only exclusively be used by Medinet or customers with written authorization to do so
 * @copyright Copyright (c) 2024 Medinet <copyright@medinet.ca>
 * @package Phoundation\Cli
 */
class CommandRequest extends Request implements CommandRequestInterface
{
    /**
     * Request class constructor
     *
     * @param string $executed_file
     * @param array|null $data
     */
    public function __construct(string $executed_file, ?array $data = null)
    {
        $this->executed_file = $executed_file;
        $this->data          = $data;
    }


}