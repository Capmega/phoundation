<?php

namespace Phoundation\Processes;



use Phoundation\Servers\Server;

/**
 * Class Processes
 *
 * This class contains methods to manage server processes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
Class Processes
{
    /**
     * Create a new process factory
     *
     * @param string|null $command
     * @param Server|null $server
     * @param bool $which_command
     * @return Process
     */
    public static function create(?string $command = null, ?Server $server = null, bool $which_command = false): Process
    {
        return new Process($command, $server, $which_command);
    }



    /*
     * Execute the specified command as a background process
     *
     * The specified command will be executed in the background in a separate process and run_background() will immediately return control back to BASE.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see show()
     * @example
     * code
     * echo current_file();
     * /code
     *
     * This will return something like
     *
     * code
     * custom.php
     * /code
     *
     * @param string $cmd The command to be executed
     * @param boolean $log If set to true, the output of the command will be logged to
     * @param boolean $single If set to true,
     * @param string $term
     * @return natural The PID of the background process executing the requested command
     */
    function run_background($cmd, $log = true, $single = true, $term = 'xterm', $wait = 0)
    {
        return include(__DIR__ . '/handlers/system-run-background.php');
    }


}