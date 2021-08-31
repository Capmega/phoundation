<?php

class Http {
    public static function headers($params, int $content_length): void
    {
        global $_CONFIG, $core;
        static $sent = false;

        if($sent) return false;
        $sent = true;

        /*
         * Ensure that from this point on we have a language configuration available
         *
         * The startup systems already configures languages but if the startup
         * itself fails, or if a show() or showdie() was issued before the startup
         * finished, then this could leave the system without defined language
         */
        if(!defined('LANGUAGE')){
            define('LANGUAGE', isset_get($_CONFIG['language']['default'], 'en'));
        }

        try{
            /*
             * Create ETAG, possibly send out HTTP304 if client sent matching ETAG
             */
            http_cache_etag();

            array_params($params, null, 'http_code', null);
            array_default($params, 'http_code', $core->register['http_code']);
            array_default($params, 'cors'     , false);
            array_default($params, 'mimetype' , $core->register['accepts']);
            array_default($params, 'headers'  , array());
            array_default($params, 'cache'    , array());

            $headers = $params['headers'];

            if($_CONFIG['security']['expose_php'] === false){
                header_remove('X-Powered-By');

            }elseif($_CONFIG['security']['expose_php'] !== true){
                /*
                 * Send custom expose header to fake X-Powered-By header
                 */
                $headers[] = 'X-Powered-By: '.$_CONFIG['security']['expose_php'];
            }

            $headers[] = 'Content-Type: '.$params['mimetype'].'; charset='.$_CONFIG['encoding']['charset'];
            $headers[] = 'Content-Language: '.LANGUAGE;

            if($content_length){
                $headers[] = 'Content-Length: '.$content_length;
            }

            if($params['http_code'] == 200){
                if(empty($params['last_modified'])){
                    $headers[] = 'Last-Modified: '.date_convert(filemtime($_SERVER['SCRIPT_FILENAME']), 'D, d M Y H:i:s', 'GMT').' GMT';

                }else{
                    $headers[] = 'Last-Modified: '.date_convert($params['last_modified'], 'D, d M Y H:i:s', 'GMT').' GMT';
                }
            }

            /*
             * Add noidex, nofollow and nosnipped headers for non production
             * environments and non normal HTTP pages.
             *
             These pages should NEVER be indexed
             */
            if(!$_CONFIG['production'] or $_CONFIG['noindex'] or !$core->callType('http')){
                $headers[] = 'X-Robots-Tag: noindex, nofollow, nosnippet, noarchive, noydir';
            }

            /*
             * CORS headers
             */
            if($_CONFIG['cors'] or $params['cors']){
                /*
                 * Add CORS / Access-Control-Allow-.... headers
                 */
                $params['cors'] = array_merge($_CONFIG['cors'], array_force($params['cors']));

                foreach($params['cors'] as $key => $value){
                    switch($key){
                        case 'origin':
                            if($value == '*.'){
                                /*
                                 * Origin is allowed from all sub domains
                                 */
                                $origin = str_from(isset_get($_SERVER['HTTP_ORIGIN']), '://');
                                $length = strlen(isset_get($_SESSION['domain']));

                                if(substr($origin, -$length, $length) === isset_get($_SESSION['domain'])){
                                    /*
                                     * Sub domain matches. Since CORS does
                                     * not support sub domains, just show
                                     * the current sub domain.
                                     */
                                    $value = $_SERVER['HTTP_ORIGIN'];

                                }else{
                                    /*
                                     * Sub domain does not match. Since CORS does
                                     * not support sub domains, just show no
                                     * allowed origin domain at all
                                     */
                                    $value = '';
                                }
                            }

                        // FALLTHROUGH

                        case 'methods':
                            // FALLTHROUGH
                        case 'headers':
                            if($value){
                                $headers[] = 'Access-Control-Allow-'.str_capitalize($key).': '.$value;
                            }

                            break;

                        default:
                            throw new BException(tr('http_headers(): Unknown CORS header ":header" specified', array(':header' => $key)), 'unknown');
                    }
                }
            }

            $headers = http_cache($params, $params['http_code'], $headers);

            /*
             * Remove incorrect headers
             */
            header_remove('X-Powered-By');
            header_remove('Expires');
            header_remove('Pragma');

            /*
             * Set correct headers
             */
            http_response_code($params['http_code']);

            if(($params['http_code'] != 200)){
                log_file(tr('Phoundation sent :http for URL ":url"', array(':http' => ($params['http_code'] ? 'HTTP'.$params['http_code'] : 'HTTP0'), ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])), 'warning', 'yellow');

            }elseif(VERBOSE){
                log_file(tr('Phoundation sent :http for URL ":url"', array(':http' => ($params['http_code'] ? 'HTTP'.$params['http_code'] : 'HTTP0'), ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])), 'http', 'green');
            }

            if(VERYVERBOSE){
                load_libs('time,numbers');
                log_console(tr('Page ":script" was processed in :time with ":usage" peak memory usage', array(':script' => $core->register['script'], ':time' => time_difference(STARTTIME, microtime(true), 'auto', 5), ':usage' => bytes(memory_get_peak_usage()))));
            }

            foreach($headers as $header){
                header($header);
            }

            if(strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD'){
                /*
                 * HEAD request, do not return a body
                 */
                die();
            }

            switch($params['http_code']){
                case 304:
                    /*
                     * 304 requests indicate the browser to use it's local cache,
                     * send nothing
                     */
                    // FALLTHROUGH

                case 429:
                    /*
                     * 429 Tell the client that it made too many requests, send
                     * nothing
                     */
                    die();
            }

            return true;

        }catch(Exception $e){
            /*
             * http_headers() itself crashed. Since http_headers()
             * would send out http 500, and since it crashed, it no
             * longer can do this, send out the http 500 here.
             */
            http_response_code(500);
            throw new BException('http_headers(): Failed', $e);
        }
    }
}