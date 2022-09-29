<?php
global $_CONFIG, $core;

try{
    if($trace_offset === null) {
        if(PLATFORM_HTTP) {
            $trace_offset = 3;

        } else {
            $trace_offset = 2;
        }

    } elseif(!is_numeric($trace_offset)) {
        throw new CoreException(tr('debug_show(): Specified $trace_offset ":trace" is not numeric', array(':trace' => $trace_offset)), 'invalid');
    }

    if(!debug()) {
        return $data;
    }

    /*
     * First cleanup data
     */
    if(is_array($data)) {
        $data = array_hide($data, 'GLOBALS,%pass,ssh_key');
    }

    $retval = '';

    if(PLATFORM_HTTP) {
        http_headers(null, 0);
    }

    if($_CONFIG['production']) {
        if(!debug()) {
            return '';
        }

// :TODO:SVEN:20130430: This should NEVER happen, send notification!
    }

    if(PLATFORM_HTTP) {
        if(empty($core->register['debug_plain'])) {
            switch($core->callType()) {
                case 'api':
                    // FALLTHROUGH
                case 'ajax':
                    /*
                     * If JSON, CORS requests require correct headers!
                     * Also force plain text content type
                     */
                    http_headers(null, 0);

                    if(!headers_sent()) {
                        header_remove('Content-Type');
                        header('Content-Type: text/plain', true);
                    }

                    echo "\n".tr('DEBUG SHOW (:file@:line) ', array(':file' => current_file($trace_offset - 1), ':line' => current_line($trace_offset - 1)))."\n";
                    print_r($data)."\n";
                    break;

                default:
                    /*
                     * Force HTML content type, and show HTML data
                     */
                    if(!headers_sent()) {
                        header_remove('Content-Type');
                        header('Content-Type: text/html', true);
                    }

                    echo debug_html($data, tr('Unknown'), $trace_offset);
                    ob_flush();
                    flush();
            }

        } else {
            echo "\n".tr('DEBUG SHOW (:file@:line) ', array(':file' => current_file($trace_offset), ':line' => current_line($trace_offset)))."\n";
            print_r($data)."\n";
            ob_flush();
            flush();
        }

        echo $retval;
        ob_flush();
        flush();

    } else {
        if(is_scalar($data)) {
            $retval .= ($quiet ? '' : tr('DEBUG SHOW (:file@:line) ', array(':file' => current_file($trace_offset), ':line' => current_line($trace_offset)))).$data."\n";

        } else {
            /*
             * Sort if is array for easier reading
             */
            if(is_array($data)) {
                ksort($data);
            }

            if(!$quiet) {
                $retval .= tr('DEBUG SHOW (:file@:line) ', array(':file' => current_file($trace_offset), ':line' => current_line($trace_offset)))."\n";
            }

            $retval .= print_r(variable_zts_safe($data), true);
            $retval .= "\n";
        }

        echo $retval;
    }

    return $data;

}catch(Exception $e) {
    if($_CONFIG['production'] or debug()) {
        /*
         * Show the error message with a conventional die() call
         */
        die(tr('show() command at ":file@:line" failed with ":e"', array(':file' => current_file($trace_offset), ':line' => current_line($trace_offset), ':e' => $e->getMessage())));
    }

    try{
        notify($e);

    }catch(Exception $e) {
        /*
         * Sigh, if notify and error_log failed as well, then there is little to do but go on
         */

    }

    return '';
}
?>