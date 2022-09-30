<?php
/*
 * Go library
 *
 * This library is used to install and execute go commands
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package go
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package go
 *
 * @return void
 */
function go_library_init() {
    try {
        load_libs('linux');

    }catch(Exception $e) {
        throw new CoreException('go_library_init(): Failed', $e);
    }
}



/*
 * Returns if the specified go file exists
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package go
 * @version 2.5.60: Added function and documentation
 * @example
 * code
 * $result = go_exists('foobar');
 * /code
 *
 * This would install the go package
 *
 * @param string $file The file to execute with go
 * @param mixed $server
 * @return string The output from the go command
 */
function go_exists($file, $server = null) {
    try {
        $exists = linux_file_exists($server, ROOT.'data/go/'.$file, $server);

        if (!$exists) {
            linux_ensure_path($server, ROOT.'data/go/');
        }

        return $exists;

    }catch(Exception $e) {
        throw new CoreException(tr('go_exists(): Failed'), $e);
    }
}



/*
 * Install the specified go packages on the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package go
 * @version 2.0.3: Added documentation
 * @example
 * code
 * $result = go_install('go');
 * /code
 *
 * This would install the go package
 *
 * @param string $file The file to execute with go
 * @param mixed $server
 * @return string The output from the go command
 */
function go_exec($params) {
    try {
        global $core;

        Arrays::ensure($params, 'commands,server');

        /*
         * Validate the requested commands, ensure that go_exec() is only used
         * when the system is ready to go
         */
        if (!$core->register['ready']) {
            throw new CoreException(tr('go_exec(): Startup has not yet finished and base is not ready to start working properly. go_exec() may not be called until configuration is fully loaded and available'), 'not-ready');
        }

        if (!$params['commands']) {
            throw new CoreException(tr('go_exec(): No commands specified'), 'not-specified');
        }

        if (!is_array($params['commands'])) {
            throw new CoreException(tr('go_exec(): Invalid commands specified'), 'invalid');
        }

        linux_ensure_package($params['server'], 'go', 'go');

        /*
         * Ensure that all scripts are executed from ROOT/scripts/
         *
         * Ensure that all arguments contain the environment specification
         */
        $count = 0;

        foreach($params['commands'] as $id => &$item) {
            if (fmod(++$count, 2)) {
                /*
                 * This must be a go command
                 */
                if (!is_string($item)) {
                    throw new CoreException(tr('go_exec(): Invalid commands structure specified, entry ":id" is an ":datatype" while it should be a string. Please ensure that $params[commands] is an array containing values with datatype string, array, string, array, etc', array(':id' => $id, ':datatype' => gettype($item))), 'invalid');
                }

// :TODO: Add support for remote server execution
                $item = ROOT.'data/go/'.$item;

            } else {
                /*
                 * These must be arguments
                 */
                if (!is_array($item)) {
                    throw new CoreException(tr('go_exec(): Invalid commands structure specified, entry ":id" is a ":datatype" while it should be an array. Please ensure that $params[commands] is an array containing values with datatype string, array, string, array, etc', array(':id' => $id, ':datatype' => gettype($item))), 'invalid');
                }
            }
        }

        /*
         * Execute the script using safe_exec
         */
        return safe_exec($params);

    }catch(Exception $e) {
        throw new CoreException(tr('go_exec(): Failed'), $e);
    }
}



/*
 * Build the specified go package
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package go
 * @version 2.0.3: Added documentation
 * @example
 * code
 * $result = go_install('go');
 * /code
 *
 * This would install the go package
 *
 * @param string $file The file to execute with go
 * @param mixed $server
 * @return string The output from the go command
 */
function go_build($path, $server) {
    try {
        $server = servers_get($server);

        if (!linux_file_exists($server, $path)) {
            throw new CoreException(tr('go_build(): Specified build path ":path" does not exist on server ":server"', array(':path' => $path, ':server' => $server)), 'not-exist');
        }

        linux_ensure_package($server, 'go', 'go');

        return servers_exec($server, array('timeout'  => 180,
                                           'function' => (PLATFORM_CLI ? 'passthru' : 'exec'),
                                           'commands' => array('cd', array($path),
                                                               'go', array('build'))));

    }catch(Exception $e) {
        throw new CoreException(tr('go_build(): Failed'), $e);
    }
}
?>
