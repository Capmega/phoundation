<?php

namespace Phoundation\Processes;

/**
 * Class Processes
 *
 * This class contains methods to manage server processes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 <copyright@capmega.com>
 * @package Phoundation\Processes
 */
Class Processes
{

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