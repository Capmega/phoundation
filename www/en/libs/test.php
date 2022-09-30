<?php
/*
 * Test library
 *
 * This library contains various test functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package test
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
 * @package test
 *
 * @return void
 */
function test_library_init() {
    try {
        $core->register['timers']['tests']['errors'] = array('all'     => array(),
                                                             'test'    => array(),
                                                             'library' => array());

        file_ensure_path(ROOT.'data/tests/contents');

        define('TESTPATH', ROOT.'data/tests/content/');

    }catch(Exception $e) {
        throw new CoreException('test_library_init(): Failed', $e);
    }
}



/*
 * Execute the specified test and show results
 */
function test($name, $description, $function) {
    global $core;

    try {
        log_console($name.' [TEST] '.$description, '', false);

        if (!is_callable($function)) {
            throw new CoreException(tr('test(): Specified function is not a function but a ":type"', array(':type' => gettype($function))), 'invalid');
        }

        $results = $function();

        if ($results) {
            echo ' ['.$results.']';
        }

        log_console(' [ OK ]', 'green');

    }catch(Exception $e) {
        log_console(' [ FAIL ]', 'red');
        log_console($e->getMessage(), 'red');

        $e = array('name'        => $name,
                   'description' => $description,
                   'trace'       => (method_exists($e, 'getMessages') ? $e->getMessages() : ''));

        $e['failure'] = isset_get($e['trace'][0]);

        array_shift($e['trace']);

        $core->register['timers']['tests']['errors']['all'][]     = $e;
        $core->register['timers']['tests']['errors']['test'][]    = $e;
        $core->register['timers']['tests']['errors']['library'][] = $e;

        return $e;
    }
}
?>
