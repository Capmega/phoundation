<?php
//if (!headers_sent()) {header_remove('Content-Type'); header('Content-Type: text/html', true);} echo "<pre>\nEXCEPTION CODE: "; print_r($e->getCode()); echo "\n\nEXCEPTION:\n"; print_r($e); echo "\n\nBACKTRACE:\n"; print_r(debug_backtrace()); die();
/*
 * Phoundation uncaught exception handler
 *
 * IMPORTANT! IF YOU ARE FACED WITH AN UNCAUGHT EXCEPTION, OR WEIRD EFFECTS LIKE
 * WHITE SCREEN, ALWAYS FOLLOW THESE STEPS:
 *
 *    Check the ROOT/data/log/syslog (or exception log if you have single_log
 *    disabled). In here you can find 99% of the issues
 *
 *    If the syslog did not contain information, then check your apache / nginx
 *    or PHP error logs. Typically you will find these in /var/log/php and
 *    /var/log/apache2 or /var/log/nginx
 *
 *    If that gives you nothing, then try uncommenting the line in the section
 *    right below these comments. This will forcibly display the error
 */

/*
 * If you are faced with an uncaught exception that does not give any
 * information (for example, "exception before platform detection", or
 * "pre ready exception"), uncomment the files line of this file to see whats up.
 *
 * The reason that this is normally commented out and that logging or displaying
 * your errors might fail is for security, as Phoundation may not know at the
 * point where your error occurred if it is on a production environment or not.
 *
 * For cases like these, uncomment the following lines and you should see your
 * error displayed on your browser.
 */
global $_CONFIG, $core;
static $executed = false;

try {
    try {
        if ($executed) {
            /*
             * We seem to be stuck in an uncaught exception loop, cut it out now!
             */
    // :TODO: ADD NOTIFICATIONS OF STUFF GOING FUBAR HERE!
            die('exception loop detected');
        }

        $executed = true;

        if (isset($core)) {
            if (empty($core->register['script'])) {
                $core->register('script', 'unknown');
            }

            if ($core->register['ready']) {
                log_file(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', array(':code' => $e->getCode(), ':type' => $core->callType(), ':script' => isset_get($core->register['script']))), 'uncaught-exception', 'exception');
                log_file($e, 'uncaught-exception', 'exception');

            } else {
                /*
                 * System is not ready, we cannot log to syslog
                 */
                error_log(tr('*** UNCAUGHT PRE-CORE-READY EXCEPTION ":code" ***', array(':code' => $e->getCode())));
                error_log($e->getMessage());
                die(1);
            }

        } else {
            error_log(tr('*** UNCAUGHT PRE-CORE-AVAILABLE EXCEPTION ":code" ***', array(':code' => $e->getCode())));
            error_log($e->getMessage(), 'uncaught-exception');
            die(1);
        }

        if (!defined('PLATFORM')) {
            /*
             * Wow, system crashed before platform detection. See $core->__constructor()
             */
            die('exception before platform detection');
        }

        switch(PLATFORM) {
            case 'cli':
                /*
                 * Ensure that required defines are available
                 */
                load_libs('cli');

                if (!defined('VERYVERBOSE')) {
                    define('VERYVERBOSE', (cli_argument('-VV,--very-verbose') ? 'VERYVERBOSE' : null));
                }

                set_timeout(1);

                $defines = array('ADMIN'    => '',
                                 'PWD'      => Strings::slash(isset_get($_SERVER['PWD'])),
                                 'VERBOSE'  => ((VERYVERBOSE or cli_argument('-V,--verbose,-V2,--very-verbose')) ? 'VERBOSE' : null),
                                 'QUIET'    => cli_argument('-Q,--quiet'),
                                 'FORCE'    => cli_argument('-F,--force'),
                                 'TEST'     => cli_argument('-T,--test'),
                                 'LIMIT'    => not_empty(cli_argument('--limit'  , true), $_CONFIG['paging']['limit']),
                                 'ALL'      => cli_argument('-A,--all'),
                                 'DELETED'  => cli_argument('--deleted'),
                                 'STATUS'   => cli_argument('-S,--status' , true),
                                 'STARTDIR' => Strings::slash(getcwd()));

                foreach($defines as $key => $value) {
                    if (!defined($key)) {
                        define($key, $value);
                    }
                }

                notify($e, false, false);

                if ($e->getCode() === 'parameters') {
                    log_console(trim(Strings::from($e->getMessage(), '():')), 'warning');
                    $GLOBALS['core'] = false;
                    die(1);
                }

                if (!$core->register['ready']) {
                    /*
                     * Configuration hasn't been loaded yet, we cannot even know if
                     * we are in debug mode or not!
                     *
                     * Log to the webserver error log files at the very least
                     */
                    if (method_exists($e, 'getMessages')) {
                        foreach($e->getMessages() as $message) {
                            error_log($message);
                        }

                    } else {
                        error_log($e->getMessage());
                    }

                    echo "\033[1;31mPre ready exception. Please check your ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information\033[0m\n";
                    print_r($e);
                    die("\033[1;31mPre ready exception. Please check your ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information\033[0m\n");
                }

                /*
                 * Command line script crashed.
                 *
                 * If not using VERBOSE mode, then try to give nice error messages
                 * for known issues
                 */
                if (!VERBOSE) {
                    if (Strings::until($e->getCode(), '/') === 'warning') {
                        /*
                         * This is just a simple general warning, no backtrace and
                         * such needed, only show the principal message
                         */
                        log_console(tr('Warning: :warning', array(':warning' => trim(Strings::from($e->getMessage(), '():')))), 'yellow');
                        $core->register['exit_code'] = 255;
                        die($core->register['exit_code']);
                    }

                    switch((string) $e->getCode()) {
                        case 'already-running':
                            log_console(tr('Failed: :message', array(':message' => trim(Strings::from($e->getMessage(), '():')))), 'yellow');
                            $core->register['exit_code'] = 254;
                            die($core->register['exit_code']);

                        case 'no-method':
                            log_console(tr('Failed: :message', array(':message' => trim(Strings::from($e->getMessage(), '():')))), 'yellow');
                            cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                            $core->register['exit_code'] = 253;
                            die($core->register['exit_code']);

                        case 'unknown-method':
                            log_console(tr('Failed: :message', array(':message' => trim(Strings::from($e->getMessage(), '():')))), 'yellow');
                            cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                            $core->register['exit_code'] = 252;
                            die($core->register['exit_code']);

                        case 'missing-arguments':
                            log_console(tr('Failed: :message', array(':message' => trim(Strings::from($e->getMessage(), '():')))), 'yellow');
                            cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                            $core->register['exit_code'] = 253;
                            die($core->register['exit_code']);

                        case 'invalid-arguments':
                            log_console(tr('Failed: :message', array(':message' => trim(Strings::from($e->getMessage(), '():')))), 'yellow');
                            cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                            $core->register['exit_code'] = 251;
                            die($core->register['exit_code']);

                        case 'validation':
                            if ($core->register['script'] === 'init') {
                                /*
                                 * In the init script, all validations are fatal!
                                 */
                                $e->makeWarning(false);
                                break;
                            }

                            if (method_exists($e, 'getMessages')) {
                                $messages = $e->getMessages();

                            } else {
                                $messages = $e->getMessage();
                            }

                            if (count($messages) > 2) {
                                array_pop($messages);
                                array_pop($messages);
                                log_console(tr('Validation failed'), 'yellow');
                                log_console($messages, 'yellow');

                            } else {
                                log_console($messages, 'yellow');
                            }

                            cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                            $core->register['exit_code'] = 250;
                            die($core->register['exit_code']);
                    }
                }

                log_console(tr('*** UNCAUGHT EXCEPTION ":code" IN CONSOLE SCRIPT ":script" ***', array(':code' => $e->getCode(), ':script' => $core->register['script'])), 'exception');
                debug(true);

                if ($e instanceof CoreException) {
                    if ($e->getCode() === 'no-trace') {
                        $messages = $e->getMessages();
                        log_console(array_pop($messages), 'exception');

                    } else {
                        /*
                         * Show the entire exception
                         */
                        $messages = $e->getMessages();
                        $data     = $e->getData();
                        $code     = $e->getCode();
                        $file     = $e->getFile();
                        $line     = $e->getLine();
                        $trace    = $e->getTrace();

                        log_console(tr('Exception code    : ":code"'      , array(':code' => $code))                  , 'exception');
                        log_console(tr('Exception location: ":file@:line"', array(':file' => $file, ':line' => $line)), 'exception');
                        log_console(tr('Exception messages trace:'), 'exception');

                        foreach($messages as $message) {
                            log_console('    '.$message, 'exception');
                        }

                        log_console('    '.$core->register['script'].': Failed', 'exception');
                        log_console(tr('Exception function trace:'), 'exception');

                        if ($trace) {
                            log_console(str_log($trace), 'exception');

                        } else {
                            log_console(tr('N/A'), 'exception');
                        }

                        if ($data) {
                            log_console(tr('Exception data:'), 'exception');
                            log_console(str_log($data), 'exception');
                        }
                    }

                } else {
                    /*
                     * Treat this as a normal PHP Exception object
                     */
                    if ($e->getCode() === 'no-trace') {
                        log_console($e->getMessage(), 'exception');

                    } else {
                        /*
                         * Show the entire exception
                         */
                        show($e, null, true);
                    }
                }

                $core->register['exit_code'] = 64;
                die(8);

            case 'http':
                /*
                 * Remove all caching headers
                 */
                if (!headers_sent()) {
                    header_remove('ETag');
                    header_remove('Cache-Control');
                    header_remove('Expires');
                    header_remove('Content-Type');
                }

                /*
                 *
                 */
                $core->register['http_code'] = 500;
                unregister_shutdown('route_404');

                /*
                 * Ensure that required defines are available
                 */
                if (!defined('VERYVERBOSE')) {
                    define('VERYVERBOSE', (getenv('VERYVERBOSE') ? 'VERYVERBOSE' : null));
                }

                log_file($e, 'uncaught-exception', 'exception');

                $defines = array('ADMIN'    => '',
                                 'PWD'      => Strings::slash(isset_get($_SERVER['PWD'])),
                                 'STARTDIR' => Strings::slash(getcwd()),
                                 'FORCE'    => (getenv('FORCE')                    ? 'FORCE'   : null),
                                 'TEST'     => (getenv('TEST')                     ? 'TEST'    : null),
                                 'VERBOSE'  => ((VERYVERBOSE or getenv('VERBOSE')) ? 'VERBOSE' : null),
                                 'QUIET'    => (getenv('QUIET')                    ? 'QUIET'   : null),
                                 'LIMIT'    => (getenv('LIMIT')                    ? 'LIMIT'   : $_CONFIG['paging']['limit']),
                                 'ORDERBY'  => (getenv('ORDERBY')                  ? 'ORDERBY' : null),
                                 'ALL'      => (getenv('ALL')                      ? 'ALL'     : null),
                                 'DELETED'  => (getenv('DELETED')                  ? 'DELETED' : null),
                                 'STATUS'   => (getenv('STATUS')                   ? 'STATUS'  : null));

                foreach($defines as $key => $value) {
                    if (!defined($key)) {
                        define($key, $value);
                    }
                }

                notify($e, false, false);

                if (!$core->register['ready']) {
                    /*
                     * Configuration hasn't been loaded yet, we cannot even know
                     * if we are in debug mode or not!
                     *
                     * Try sending the right response code and content type
                     * headers so that at least there will be a visible page
                     * with the right mimetype
                     */
                    if (!headers_sent()) {
                        header('Content-Type: text/html', true);
                    }


                    if (method_exists($e, 'getMessages')) {
                        foreach($e->getMessages() as $message) {
                            error_log($message);
                        }

                    } else {
                        error_log($e->getMessage());
                    }

                    die(tr('Pre ready exception. Please check your ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information'));
                }

                if ($e->getCode() === 'validation') {
                    $e->setCode(400);
                }

                if (($e instanceof CoreException) and is_numeric($e->getRealCode()) and ($e->getRealCode() > 100) and page_show($e->getRealCode(), array('exists' => true))) {
                    if ($e->isWarning()) {
                        html_flash_set($e->getMessage(), 'warning', $e->getRealCode());
                    }

                    log_file(tr('Displaying exception page ":page"', array(':page' => $e->getRealCode())), 'exceptions', 'error');
                    page_show($e->getRealCode(), array('message' =>$e->getMessage()));
                }

                if (debug()) {
                    /*
                     * We're trying to show an html error here!
                     */
                    if (!headers_sent()) {
                        http_response_code(500);
                        header('Content-Type: text/html', true);
                    }

                    switch($core->callType()) {
                        case 'api':
                            // FALLTHROUGH
                        case 'ajax':
                            echo "UNCAUGHT EXCEPTION\n\n";
                            showdie($e);
                    }

                    $retval = ' <style type="text/css">
                                table.exception{
                                    font-family: sans-serif;
                                    width:99%;
                                    background:#AAAAAA;
                                    border-collapse:collapse;
                                    border-spacing:2px;
                                    margin: 5px auto 5px auto;
                                }
                                td.center{
                                    text-align: center;
                                }
                                table.exception thead{
                                    background: #CE0000;
                                    color: white;
                                    font-weight: bold;
                                }
                                table.exception td{
                                    border: 1px solid black;
                                    padding: 15px;
                                }
                                table.exception td.value{
                                    word-break: break-all;
                                }
                                table.debug{
                                    background:#AAAAAA !important;
                                }
                                table.debug thead{
                                    background: #CE0000 !important;
                                    color: white;
                                }
                                table.debug .debug-header{
                                    display: none;
                                }
                                </style>
                                <table class="exception">
                                    <thead>
                                        <td colspan="2" class="center">
                                            '.tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', array(':code' => $e->getCode(), ':script' => $core->register['script'], 'type' => $core->callType())).'
                                        </td>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="center">
                                                '.tr('An uncaught exception with code ":code" occured in script ":script". See the exception core dump below for more information on how to fix this issue', array(':code' => $e->getCode(), ':script' => $core->register['script'])).'
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                '.tr('File').'
                                            </td>
                                            <td>
                                                '.$e->getFile().'
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                '.tr('Line').'
                                            </td>
                                            <td>
                                                '.$e->getLine().'
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>';

                    echo $retval;

                    if ($e instanceof CoreException) {
                        /*
                         * Clean data
                         */
                        $e->setData(array_hide(Arrays::force($e->getData()), 'GLOBALS,%pass,ssh_key'));
                    }

                    showdie($e);
                }

                /*
                 * We're not in debug mode.
                 */
                notify($e, false, false);

                switch($core->callType()) {
                    case 'api':
                        // FALLTHROUGH
                    case 'ajax':
                        if ($e instanceof CoreException) {
                            json_message($e->getRealCode(), array('reason' => ($e->isWarning() ? trim(Strings::from($e->getMessage(), ':')) : '')));
                        }

                        /*
                         * Assume that all non CoreException exceptions are not
                         * warnings!
                         */
                        json_message($e->getCode(), array('reason' => ''));
                }

                page_show($e->getCode());
        }

    }catch(Exception $f) {
        if (!isset($core)) {
            error_log(tr('*** UNCAUGHT PRE CORE AVAILABLE EXCEPTION HANDLER CRASHED ***'));
            error_log(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));
            error_log($f->getMessage());
            die('Pre core available exception with handling failure. Please your application or webserver error log files, or enable the first line in the exception handler file for more information');
        }

        if (!defined('PLATFORM') or !$core->register['ready']) {
            error_log(tr('*** UNCAUGHT PRE READY EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => $core->register['script'])));
            error_log(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));
            error_log($f->getMessage());
            die('Pre core ready exception with handling failure. Please check your ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information');
        }

        log_file('STARTUP-UNCAUGHT-EXCEPTION HANDLER CRASHED!', 'exception-handler', 'exception');
        log_file($f, 'exception-handler');

        switch(PLATFORM) {
            case 'cli':
                log_console(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => $core->register['script'])), 'exception');
                log_console(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'), 'exception');

                debug(true);
                show($f);
                showdie($e);

            case 'http':
                if (!headers_sent()) {
                    http_response_code(500);
                    header('Content-Type: text/html');
                }

                if (!debug()) {
                    notify($f, false, false);
                    notify($e, false, false);
                    page_show(500);
                }

                show(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => $core->register['script'])));
                show('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***');

                show($f);
                showdie($e);
        }
    }

}catch(Exception $g) {
    /*
     * Well, we tried. Here we just give up all together
     */
    die("Fatal error. check ROOT/data/syslog, application server logs, or webserver logs for more information\n");
}
