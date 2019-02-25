<?php
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
 * "pre ready exception"), uncomment the following line to see whats up.
 *
 * The reason that this is normally commented out and that logging or displaying
 * your errors might fail is for security, as Phoundation may not know at the
 * point where your error occurred if it is on a production environment or not.
 *
 * For cases like these, uncomment the following lines and you should see your
 * error displayed on your browser.
 */
//echo "<pre>\nEXCEPTION CODE: "; print_r($e->getCode()); echo "\n\nEXCEPTION:\n"; print_r($e); echo "\n\nBACKTRACE:\n"; print_r(debug_backtrace()); die();

global $_CONFIG, $core;
static $executed = false;

try{
    if($executed){
        /*
         * We seem to be stuck in an uncaught exception loop, cut it out now!
         */
// :TODO: ADD NOTIFICATIONS OF STUFF GOING FUBAR HERE!
        die('exception loop detected');
    }

    $executed = true;

    if(isset($core)){
        if(empty($core->register['script'])){
            $core->register('script', 'unknown');
        }

        if($core->register['ready']){
            log_file(tr('*** UNCAUGHT PRE-CORE-READY EXCEPTION ":code" IN ":type" SCRIPT ":script" ***', array(':code' => $e->getCode(), ':type' => $core->callType(), ':script' => $core->register['script'])), 'exceptions', 'error');
            log_file($e, 'exceptions');
        }

    }else{
        error_log(tr('*** UNCAUGHT PRE-CORE-AVAILABLE EXCEPTION ":code" ***', array(':code' => $e->getCode())), 'exceptions', 'error');
        error_log($e, 'exceptions');

        echo tr('*** UNCAUGHT PRE-CORE-AVAILABLE EXCEPTION ":code" ***', array(':code' => $e->getCode()));
        die();
    }

    if(!defined('PLATFORM')){
        /*
         * Wow, system crashed before platform detection. See $core->__constructor()
         */
        die('exception before platform detection');
    }

    switch(PLATFORM){
        case 'cli':
            /*
             * Ensure that required defines are available
             */
            load_libs('cli');

            if(!defined('VERYVERBOSE')){
                define('VERYVERBOSE', (cli_argument('-VV,--very-verbose') ? 'VERYVERBOSE' : null));
            }

            $defines = array('ADMIN'    => '',
                             'PWD'      => slash(isset_get($_SERVER['PWD'])),
                             'VERBOSE'  => ((VERYVERBOSE or cli_argument('-V,--verbose,-V2,--very-verbose')) ? 'VERBOSE' : null),
                             'QUIET'    => cli_argument('-Q,--quiet'),
                             'FORCE'    => cli_argument('-F,--force'),
                             'NOCOLOR'  => cli_argument('-C,--no-color'),
                             'TEST'     => cli_argument('-T,--test'),
                             'LIMIT'    => not_empty(cli_argument('--limit', true), $_CONFIG['paging']['limit']),
                             'ALL'      => cli_argument('-A,--all'),
                             'DELETED'  => cli_argument('--deleted'),
                             'STATUS'   => cli_argument('-S,--status' , true),
                             'STARTDIR' => slash(getcwd()));

            foreach($defines as $key => $value){
                if(!defined($key)){
                    define($key, $value);
                }
            }

            if($e->getCode() === 'parameters'){
                log_console(trim(str_from($e->getMessage(), '():')), 'warning');
                $GLOBALS['core'] = false;
                die(1);
            }

            if(!$core->register['ready']){
                /*
                 * Configuration hasn't been loaded yet, we cannot even know if
                 * we are in debug mode or not!
                 *
                 * Log to the webserver error log at the very least
                 */
                if(method_exists($e, 'getMessages')){
                    foreach($e->getMessages() as $message){
                        error_log($message);
                    }

                }else{
                    error_log($e->getMessage());
                }

                echo "\033[1;31mPre ready exception\033[0m\n";
                print_r($e);
                die("\033[1;31mPre ready exception\033[0m\n");
            }

            /*
             * Command line script crashed.
             *
             * If not using VERBOSE mode, then try to give nice error messages
             * for known issues
             */
            if(!VERBOSE){
                if(str_until($e->getCode(), '/') === 'warning'){
                    /*
                     * This is just a simple general warning, no backtrace and
                     * such needed, only show the principal message
                     */
                    log_console(tr('Warning: :warning', array(':warning' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                    $core->register['exit_code'] = 255;
                    die($core->register['exit_code']);
                }

                switch((string) $e->getCode()){
                    case 'already-running':
                        log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                        $core->register['exit_code'] = 254;
                        die($core->register['exit_code']);

                    case 'no-method':
                        log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                        cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                        $core->register['exit_code'] = 253;
                        die($core->register['exit_code']);

                    case 'unknown-method':
                        log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                        cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                        $core->register['exit_code'] = 252;
                        die($core->register['exit_code']);

                    case 'missing-arguments':
                        log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                        cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                        $core->register['exit_code'] = 253;
                        die($core->register['exit_code']);

                    case 'invalid-arguments':
                        log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                        cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                        $core->register['exit_code'] = 251;
                        die($core->register['exit_code']);

                    case 'validation':
                        if(method_exists($e, 'getMessages')){
                            $messages = $e->getMessages();

                        }else{
                            $messages = $e->getMessage();
                        }

                        if(count($messages) > 2){
                            array_pop($messages);
                            array_pop($messages);
                            log_console(tr('Validation failed'), 'yellow');
                            log_console($messages, 'yellow');

                        }else{
                            log_console($messages, 'yellow');
                        }

                        cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                        $core->register['exit_code'] = 250;
                        die($core->register['exit_code']);
                }
            }

            log_console(tr('*** UNCAUGHT EXCEPTION ":code" IN CONSOLE SCRIPT ":script" ***', array(':code' => $e->getCode(), ':script' => $core->register['script'])), 'red');
            debug(true);

            if($e instanceof BException){
                if($e->getCode() === 'no-trace'){
                    $messages = $e->getMessages();
                    log_console(array_pop($messages), 'red');

                }else{
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
                    foreach($messages as $message){
                        log_console('    '.$message, 'exception');
                    }

                    log_console('    '.$core->register['script'].': Failed', 'exception');
                    log_console(tr('Exception function trace:'), 'exception');

                    if($trace){
                        show($trace, null, true);

                    }else{
                        show('N/A');
                    }

                    if($data){
                        log_console(tr('Exception data:'), 'exception');
                        show($data, null, true);
                    }
                }

            }else{
                /*
                 * Treat this as a normal PHP Exception object
                 */
                if($e->getCode() === 'no-trace'){
                    log_console($e->getMessage(), 'red');

                }else{
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
             * Ensure that required defines are available
             */
            if(!defined('VERYVERBOSE')){
                define('VERYVERBOSE', (getenv('VERYVERBOSE') ? 'VERYVERBOSE' : null));
            }

            $defines = array('ADMIN'    => '',
                             'PWD'      => slash(isset_get($_SERVER['PWD'])),
                             'STARTDIR' => slash(getcwd()),
                             'FORCE'    => (getenv('FORCE')                    ? 'FORCE'   : null),
                             'NOCOLOR'  => (getenv('NOCOLOR')                  ? 'NOCOLOR' : null),
                             'TEST'     => (getenv('TEST')                     ? 'TEST'    : null),
                             'VERBOSE'  => ((VERYVERBOSE or getenv('VERBOSE')) ? 'VERBOSE' : null),
                             'QUIET'    => (getenv('QUIET')                    ? 'QUIET'   : null),
                             'LIMIT'    => (getenv('LIMIT')                    ? 'LIMIT'   : $_CONFIG['paging']['limit']),
                             'ORDERBY'  => (getenv('ORDERBY')                  ? 'ORDERBY' : null),
                             'ALL'      => (getenv('ALL')                      ? 'ALL'     : null),
                             'DELETED'  => (getenv('DELETED')                  ? 'DELETED' : null),
                             'STATUS'   => (getenv('STATUS')                   ? 'STATUS'  : null));

            foreach($defines as $key => $value){
                if(!defined($key)){
                    define($key, $value);
                }
            }

            if(!$core->register['ready']){
                /*
                 * Configuration hasn't been loaded yet, we cannot even know if we are
                 * in debug mode or not!
                 */
                if(!headers_sent()){
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                }

                if(method_exists($e, 'getMessages')){
                    foreach($e->getMessages() as $message){
                        error_log($message);
                    }

                }else{
                    error_log($e->getMessage());
                }

                die('Pre ready exception');
            }

            if($e->getCode() === 'validation'){
                $e->setCode(400);
            }

            if(($e instanceof BException) and is_numeric($e->getRealCode()) and page_show($e->getRealCode(), array('exists' => true))){
                if($e->isWarning()){
                    html_flash_set($e->getMessage(), 'warning', $e->getRealCode());
                }

                log_file(tr('Displaying exception page ":page"', array(':page' => $e->getRealCode())), 'exceptions', 'error');
                page_show($e->getRealCode(), array('message' =>$e->getMessage()));
            }

            if(debug()){
                if(!headers_sent()){
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                }

                switch($core->callType()){
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

                if($e instanceof BException){
                    /*
                     * Clean data
                     */
                    $e->setData(array_hide($e->getData(), 'GLOBALS,%pass,ssh_key'));
                }

                showdie($e);
            }

            notify($e);

            switch($core->callType()){
                case 'api':
                    // FALLTHROUGH
                case 'ajax':
                    $code = 500;

                    if(is_numeric($e->getCode()) and ($e->getCode() > 100)){
                        $code = $e->getCode();
                    }

                    json_error(tr('Something went wrong, please try again later'), '', '', $code);
            }

            page_show(500);
    }

}catch(Exception $f){
    if(!isset($core)){
        error_log(tr('*** UNCAUGHT PRE CORE AVAILABLE EXCEPTION HANDLER CRASHED ***'));
        error_log(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));
        error_log($f->getMessage());
        die('Pre core available exception with handling failure');
    }

    if(!defined('PLATFORM') or !$core->register['ready']){
        error_log(tr('*** UNCAUGHT PRE READY EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => $core->register['script'])));
        error_log(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));
        error_log($f->getMessage());
        die('Pre ready core exception with handling failure');
    }

    log_file('STARTUP-UNCAUGHT-EXCEPTION HANDLER CRASHED!', 'exception-handler', 'red');
    log_file($f, 'exception-handler');

    switch(PLATFORM){
        case 'cli':
            log_console(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => $core->register['script'])), 'red');
            log_console(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'), 'red');

            debug(true);
            show($f);
            showdie($e);

        case 'http':
            if(!debug()){
                notify($f);
                notify($e);
                page_show(500);
            }

            show(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => $core->register['script'])));
            show('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***');

            show($f);
            showdie($e);
    }
}
?>
