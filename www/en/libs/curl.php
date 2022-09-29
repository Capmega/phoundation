<?php
/*
 * cURL library
 *
 * Functions used for cURL things
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package curl
 * @module curl
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-clients
 *
 * @return void
 */
function curl_library_init() {
    try{
        if(!extension_loaded('curl')) {
            throw new CoreException(tr('curl_library_init(): The PHP "curl" module is not available, please install it first. On ubuntu install the module with "apt -y install php-curl"; a restart of the webserver or php fpm server may be required'), 'missing-module');
        }

        load_config('curl');

    }catch(Exception $e) {
        throw new CoreException('curl_library_init(): Failed', $e);
    }
}



/*
 * Get data using an sven HTTP proxy server
 */
function curl_get_proxy($url, $file = '', $serverurl = null) {
    global $_CONFIG;

    try{
        if(!$serverurl) {
            $serverurl = $_CONFIG['curl']['proxies'];
        }

        if(is_array($serverurl)) {
            $serverurl = array_random_value($serverurl);
        }

        if(is_array($url)) {
            throw new CoreException(tr('curl_get_proxy(): No URL specified'), 'not-specified');
        }

        if(!$serverurl) {
            throw new CoreException(tr('curl_get_proxy(): No proxy server URL(s) specified'), 'not-specified');
        }

        log_console(tr('Using proxy ":proxy"', array(':proxy' => Strings::cut((str_log($serverurl), '://', '/'))), 'VERBOSE');

        $data = curl_get(array('url'        => Strings::endsWith($serverurl, '?apikey='.$_CONFIG['curl']['apikey'].'&url=').urlencode($url),
                               'getheaders' => false,
                               'proxies'    => false));

        if(!trim($data['data'])) {
            throw new CoreException(tr('curl_get_proxy(): Proxy returned no data. Is proxy correctly configured? Proxy domain resolves correctly?'), 'no-data');
        }

        if(substr($data['data'], 0, 12) !== 'PROXY_RESULT') {
            throw new CoreException(tr('curl_get_proxy(): Proxy returned invalid data ":data" from proxy ":proxy". Is proxy correctly configured? Proxy domain resolves correctly?', array(':data' => str_log($data), ':proxy' => Strings::cut((str_log($serverurl), '://', '/'))), 'not-specified');
        }

        $data         = substr($data['data'], 12);
        $data         = json_decode_custom($data);
        $data['data'] = base64_decode($data['data']);

        if($file) {
            /*
             * Write the data to the specified file
             */
            file_put_contents($file, $data['data']);
        }

        return $data;

    }catch(Exception $e) {
        throw new CoreException('curl_get_proxy(): Failed', $e);
    }
}



/*
 * Returns a random IP from the pool of all IP's available on this computer
 * 127.0.0.1 will NOT be returned, all other IP's will
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package curl
 * @todo Implement IPv6 support! The variable is there, but
 * @see curl_get_random_ip()
 * @version 2.4.60 Added function and documentation
 *
 * @param boolean $ipv4 If set to true, IPv4 addresses are also returned
 * @param boolean $ipv6 If set to true, IPv6 addresses are also returned
 * @param boolean $localhost If set to true, the localhost ip 127.0.0.1 is also returned
 * @return array All IP addresses available on this server
 */
function curl_list_ips($ipv4 = true, $ipv6 = false, $localhost = true) {
    global $col;

    try{
        try{
            $results = safe_exec(array('commands' => array('/sbin/ifconfig', array('connector' => '|'),
                                                           'egrep'         , array('-i', 'addr|inet'))));

            $results = implode("\n", $results);

        }catch(Exception $e) {
            throw new CoreException(tr('curl_list_ips(): Failed to execute ifconfig, it probably is not installed. On Ubuntu install it by executing "sudo apt install net-toolks"'), $e);
        }

        if(!preg_match_all('/(?:addr|inet)6?(?:\:| )(.+?) /', $results, $matches)) {
            throw new CoreException('curl_list_ips(): ifconfig returned no IPs', 'not-exists');
        }

        if(!$matches or empty($matches[1])) {
            throw new CoreException('curl_list_ips(): No IP data found', 'not-exists');
        }

        $flags   = FILTER_VALIDATE_IP;
        $options = null;
        $ips     = array();

        if(!$ipv4) {
            if(!$ipv6) {
                throw new CoreException('curl_list_ips(): Both IPv4 and IPv6 IP\'s are specified to be disallowed', 'not-exists');
            }

            $options = $options | FILTER_FLAG_IPV6;

        } elseif(!$ipv6) {
            $options = $options | FILTER_FLAG_IPV4;
        }

        foreach($matches[1] as $ip) {
            if(!$ip) {
                continue;
            }

            $ip = str_replace(':', '', $ip);
            $ip = trim(Strings::from($ip, 'addr'));

            if($ip == '127.0.0.1') {
                if(!$localhost) {
                    continue;
                }
            }

            if(filter_var($ip, $flags, $options)) {
                $ips[] = $ip;
            }
        }

        if(!$ips) {
            throw new CoreException(tr('curl_list_ips(): Failed to find any IP addresses'), 'failed');
        }

        return $ips;

    }catch(Exception $e) {
        throw new CoreException('curl_list_ips(): Failed', $e);
    }
}



/*
 * Returns a random IP from the pool of all IP's available on this computer
 * 127.0.0.1 will NOT be returned, all other IP's will
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package curl
 * @see curl_list_ips()
 * @version 2.4.60 Added function and documentation
 *
 * @param boolean $ipv4 If set to true, IPv4 addresses are also returned
 * @param boolean $ipv6 If set to true, IPv6 addresses are also returned
 * @param boolean $localhost If set to true, the localhost ip 127.0.0.1 can also be returned
 * @return string A random IP address available on this server
 */
function curl_get_random_ip($ipv4 = true, $ipv6 = false, $localhost = false) {
    global $col;

    try{
        $ips = curl_list_ips($ipv4, $ipv6, $localhost);
        $ip  = $ips[array_rand($ips)];

        return $ip;

    }catch(Exception $e) {
        throw new CoreException('curl_get_random_ip(): Failed', $e);
    }
}



///*
// *
// */
//function curl_get_random_ip() {
//    global $col;
//
//    try{
//        $ips = explode("\n", safe_exec('/sbin/ifconfig|grep "Mask:255.255.255.0"|sed "s/^.*addr://"|sed "s/ .*//"'));
//
//        shuffle($ips);
//
//        if(!$ips) {
//            return '';
//        }
//
//        return $ips[0];
//
//    }catch(Exception $e) {
//        throw new CoreException('curl_get_random_ip(): Failed', $e);
//    }
//}



/*
 * Get files from the internet
 */
function curl_get($params, $referer = null, $post = false, $options = array()) {
    static $retry;
    global $_CONFIG;

    try{
        array_params($params, 'url');
        array_default($params, 'referer'        , $referer);
        array_default($params, 'useragent'      , $_CONFIG['curl']['user_agents']);
        array_default($params, 'post'           , $post);
        array_default($params, 'posturlencoded' , false);
        array_default($params, 'options'        , $options);
        array_default($params, 'ch'             , false);
        array_default($params, 'close'          , ($params['ch'] ? false : true));
        array_default($params, 'getdata'        , true);
        array_default($params, 'getstatus'      , true);
        array_default($params, 'cookies'        , true);
        array_default($params, 'file'           , false);
        array_default($params, 'getcookies'     , false);
        array_default($params, 'getheaders'     , true);
        array_default($params, 'followlocation' , true);
        array_default($params, 'httpheaders'    , true);
        array_default($params, 'content-type'   , false);
        array_default($params, 'cache'          , false);
        array_default($params, 'verbose'        , VERBOSE);
        array_default($params, 'method'         , null);
        array_default($params, 'dns_cache'      , true);
        array_default($params, 'verify_ssl'     , true);
        array_default($params, 'user_pwd'       , false);
        array_default($params, 'proxies'        , $_CONFIG['curl']['proxies']);
        array_default($params, 'simulation'     , false);                               // false, partial, or full
        array_default($params, 'sleep'          , 1000);                                // Sleep howmany microseconds between retries
        array_default($params, 'retries'        , $_CONFIG['curl']['retries']);         // Retry howmany time on HTTP0 failures
        array_default($params, 'timeout'        , $_CONFIG['curl']['timeout']);         // # of seconds for cURL functions to execute
        array_default($params, 'connect_timeout', $_CONFIG['curl']['connect_timeout']); // # of seconds before connection try will fail
        array_default($params, 'log'            , $_CONFIG['curl']['log']);             // Log the output of cURL

        if($params['simulation']) {
            log_console(tr('Simulating cURL request to URL ":url"', array(':url' => $params['url'])), 'VERBOSE/cyan');

        } else {
            log_console(tr('Making cURL request to URL ":url"', array(':url' => $params['url'])), 'VERBOSE/cyan');
        }

        if($params['proxies']) {
            return curl_get_proxy($params['url'], $params['file']);
        }

        if($params['httpheaders'] === true) {
            /*
             * Send default headers
             *
             * Check if we're sending files. If so, use multipart
             */
            if(is_array($params['post'])) {
                foreach($params['post'] as $post) {
                    if(is_object($post) and ($post instanceof CURLFile)) {
                        $multipart = true;
                        break;
                    }
                }
            }

            if(empty($multipart)) {
                $params['httpheaders'] = array('Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                                               'Cache-Control: max-age=0',
                                               'Connection: keep-alive',
                                               'Keep-Alive: 300',
                                               'Expect:',
                                               'Accept-Charset: utf-8,ISO-8859-1;q=0.7,*;q=0.7',
                                               'Accept-Language: en-us,en;q=0.5');

            } else {
                $params['httpheaders'] = array('Content-Type: multipart/form-data',
                                               'boundary={-0-0-0-0-0-(00000000000000000000)-0-0-0-0-0-}',
                                               'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                                               'Cache-Control: max-age=0',
                                               'Connection: keep-alive',
                                               'Keep-Alive: 300',
                                               'Expect:',
                                               'Accept-Charset: utf-8,ISO-8859-1;q=0.7,*;q=0.7',
                                               'Accept-Language: en-us,en;q=0.5');
            }

        } elseif($params['httpheaders'] and !is_array($params['httpheaders'])) {
            throw new CoreException('curl_get(): Invalid headers specified', 'not-specified');
        }

        if(empty($params['url'])) {
            throw new CoreException('curl_get(): No URL specified', 'not-specified');
        }

        /*
         * Use the already existing cURL data array
         */
        if(empty($params['curl'])) {
            $retval = array('simulation' => $params['simulation']);

        } else {
            $retval               = $params['curl'];
            $params['ch']         = $params['curl']['ch'];
            $params['simulation'] = $params['curl']['simulation'];
            $params['close']      = false;
        }

        if(is_array($params['useragent'])) {
            $params['useragent'] = array_get_random($params['useragent']);
        }

        /*
         * Use the already existing cURL connection?
         */
        if($params['ch']) {
            /*
             * Use an existing cURL connection
             */
            $ch = $params['ch'];
            curl_setopt($ch, CURLOPT_URL, $params['url']);

        } else {
            /*
             * Create a new cURL connection
             */
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL           ,  $params['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true);
            curl_setopt($ch, CURLOPT_USERAGENT     , ($params['useragent'] ? $params['useragent'] : curl_get_random_user_agent()));
            curl_setopt($ch, CURLOPT_INTERFACE     ,  curl_get_random_ip());
            curl_setopt($ch, CURLOPT_TIMEOUT       ,  $params['timeout']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,  $params['connect_timeout']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ($params['verify_ssl'] ? 2 : 0));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, ($params['verify_ssl'] ? 1 : 0));

            if($params['user_pwd']) {
                curl_setopt($ch, CURLOPT_USERPWD,  $params['user_pwd']);
            }

            if($params['log']) {
                if($params['log'] === true) {
                    $params['log'] = ROOT.'data/log/curl';
                }

                file_ensure_path(dirname($params['log']));
                curl_setopt($ch, CURLOPT_STDERR, fopen($params['log'], 'a'));

                if($params['post']) {
                    log_database(tr('curl_get(): POST ":url" with data ":data"', array(':url' => $params['url'], ':data' => $params['post'])), 'curl/debug');

                } else {
                    log_database(tr('curl_get(): GET ":url"', array(':url' => $params['url'])), 'curl/debug');
                }
            }

            if($params['method']) {
                $params['method'] = strtoupper($params['method']);

                switch($params['method']) {
                    case 'POST':
                        // FALLTHROUGH
                    case 'HEAD':
                        // FALLTHROUGH
                    case 'PUT':
                        // FALLTHROUGH
                    case 'DELETE':
                        // FALLTHROUGH
                    case 'OPTIONS':
                        // FALLTHROUGH
                    case 'TRACE':
                        // FALLTHROUGH
                    case 'CONNECT':
                        /*
                         * Use a different method than GET
                         */
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST , $params['method']);
                        break;

                    case 'GET':
                        break;
                }
            }

            /*
             * Use cookies?
             */
            if(isset_get($params['cookies'])) {
                if(!isset_get($params['cookie_file'])) {
                    $params['cookie_file'] = file_temp();
                }

                $retval['cookie_file'] = $params['cookie_file'];

                /*
                 * Make sure the specified cookie path exists
                 */
                file_ensure_path(dirname($params['cookie_file']));

                /*
                 * Set cookie options
                 */
                curl_setopt($ch, CURLOPT_COOKIEJAR     , $params['cookie_file']);
                curl_setopt($ch, CURLOPT_COOKIEFILE    , $params['cookie_file']);
                curl_setopt($ch, CURLOPT_COOKIESESSION , true);
            }
        }

        curl_setopt($ch, CURLOPT_VERBOSE, not_empty($params['verbose'], null));
        curl_setopt($ch, CURLOPT_REFERER, not_empty($params['referer'], null));
        curl_setopt($ch, CURLOPT_HEADER , ($params['getcookies'] or $params['getheaders'] ?  1 : 0));

        if($params['post'] !== false) {
            /*
             * Disable 301 302 location header following since this would cause the POST to go to GET
             */
            $params['followlocation'] = false;

            if($params['content-type']) {
                curl_setopt($ch, CURLINFO_CONTENT_TYPE, $params['content-type']);
            }

            curl_setopt($ch, CURLOPT_POST, 1);

//            if($params['utf8']) {
//                /*
//                 * Set UTF8 transfer header
//                 */
//                if(!$params['httpheaders']) {
//                    $params['httpheaders'] = array();
//                }
//application/x-www-form-urlencoded
////                $params['httpheaders'][] = 'Content-Type: application/x-www-form-urlencoded; charset='.$_CONFIG['encoding']['charset'].';';
////                $params['httpheaders'][] = 'Content-Type: application/x-www-form-urlencoded; charset='.$_CONFIG['encoding']['charset'].';';
////                $params['httpheaders'][] = 'Content-Type: text/html; charset='.strtolower($_CONFIG['encoding']['charset']).';';
//            }

            if($params['posturlencoded']) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params['post']));

            } else {
                try{
                    curl_setopt($ch, CURLOPT_POSTFIELDS , $params['post']);

                }catch(Exception $e) {
                    if(strstr($e->getMessage(), 'Array to string conversion')) {
                        throw new CoreException(tr('curl_get(): CURLOPT_POSTFIELDS failed with "Array to string conversion", this is very likely because the specified post array is a multi dimensional array, while CURLOPT_POSTFIELDS only accept one dimensional arrays. Please set $params[posturlencoded] = true to use http_build_query() to set CURLOPT_POSTFIELDS instead'), 'invalid');
                    }
                }
            }

        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, ($params['followlocation'] ?  1 : 0));
        curl_setopt($ch, CURLOPT_MAXREDIRS     , ($params['followlocation'] ? 50 : null));

        if($params['httpheaders'] !== false) {
//show($params['httpheaders']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $params['httpheaders']);
        }

        /*
         * Disable DNS cache?
         */
        if(!$params['dns_cache']) {
            $params['options'][CURLOPT_DNS_CACHE_TIMEOUT] = 0;
        }

        /*
         * Apply more cURL options
         */
        if($params['options']) {
            foreach($params['options'] as $key => $value) {
                curl_setopt($ch, $key, $value);
            }
        }

        if($params['cache']) {
            if($retval = sql_get('SELECT `data` FROM `curl_cache` WHERE `url` = :url', true, array(':url' => $params['url']))) {
                $retry = 0;
                return json_decode_custom($retval);
            }
        }

        if($params['getdata']) {
            if($params['simulation'] === false) {
                $retval['data'] = curl_exec($ch);

                if(curl_errno($ch)) {
                    throw new CoreException(tr('curl_get(): CURL failed with ":e"', array(':e' => curl_strerror(curl_errno($ch)))), 'CURL'.curl_errno($ch));
                }

            } elseif(($params['simulation'] === 'full') or ($params['simulation'] === 'partial')) {
                $retval['data'] = $params['simulation'];

            } else {
                throw new CoreException(tr('curl_get(): Unknown simulation type ":simulation" specified. Please use either false, "partial" or "full"', array(':simulation' => $params['simulation'])), 'unknown');
            }
        }

        if(VERYVERBOSE) {
            log_console(tr('cURL result status:'));

            $retval['status'] = curl_getinfo($ch);

            foreach($retval['status'] as $key => $value) {
                log_console(cli_color($key.' : ', 'white').str_force($value));
            }
        }

        if($params['getstatus']) {
            if($params['simulation']) {
                $retval['status'] = array('http_code'  => 200,
                                          'simulation' => true);

            } else {
                $retval['status'] = curl_getinfo($ch);
            }
        }

        if($params['getcookies']) {
            /*
             * get cookies
             */
            preg_match('/^Set-Cookie:\s*([^;]*)/mi', $retval['data'], $matches);

            if(empty($matches[1])) {
                $retval['cookies'] = array();

            } else {
                parse_str($matches[1], $retval['cookies']);
            }
        }

        if($params['close']) {
            /*
             * Close this cURL session
             */
            if(!empty($retval['cookie_file'])) {
                file_delete($retval['cookie_file']);
            }

            unset($retval['cookie_file']);
            curl_close($ch);

        } else {
            $retval['ch']  = $ch;
            $retval['url'] = $params['url'];
        }

        if($params['cache']) {
            unset($retval['ch']);

            sql_query('INSERT INTO `curl_cache` (`users_id`, `url`, `data`)
                       VALUES                   (:users_id , :url , :data )

                       ON DUPLICATE KEY UPDATE `data` = :data_update',

                      array(':users_id'    => (empty($_SESSION['user']['id']) ? null : $_SESSION['user']['id']),
                            ':url'         => $params['url'],
                            ':data'        => json_encode_custom($retval),
                            ':data_update' => json_encode_custom($retval)));
        }

        switch($retval['status']['http_code']) {
            case 200:
                break;

            case 403:
                try{
                    $data = json_decode_custom($retval['data']);

                }catch(Exception $e) {
                    $data = array('message' => tr('Failed to decode URL data ":data"', array(':data' => $retval['data'])));
                }

                throw new CoreException(tr('curl_get(): URL ":url" gave HTTP "403" ACCESS DENIED because ":data"', array(':url' => $params['url'], ':data' => $data)), 'HTTP'.$retval['status']['http_code'], $retval);

            default:
                throw new CoreException(tr('curl_get(): URL ":url" gave HTTP ":httpcode"', array(':url' => $params['url'], ':httpcode' => $retval['status']['http_code'])), 'HTTP'.$retval['status']['http_code'], $retval);
        }

        if($params['file']) {
            file_put_contents($params['file'], $retval['data']);
        }

        $retry = 0;
        return $retval;

    }catch(Exception $e) {
        if((($e->getCode() == 'HTTP0') or ($e->getCode() == 'CURL28')) and (++$retry <= $params['retries'])) {
            /*
             * For whatever reason, connection gave HTTP code 0 which probably
             * means that the server died off completely. This again may mean
             * that the server overloaded. Wait for a few seconds, and try again
             * for a limited number of times
             *
             */
            usleep($params['sleep']);
            log_console(tr('curl_get(): Got HTTP0 for url ":url" at attepmt ":retry" with ":connect_timeout" seconds connect timeout', array(':url' => $params['url'], ':retry' => $retry, ':connect_timeout' => $params['connect_timeout'])), 'yellow');
            return curl_get($params, $referer, $post, $options);
        }

        throw new CoreException(tr('curl_get(): Failed to get url ":url"', array(':url' => $params['url'])), $e);
    }
}



/*
 * Return random user agent
 */
function curl_get_random_user_agent() {
    $agents = array('Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322; Alexa Toolbar)',
                    'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1) Gecko/20021204',
                    'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
                    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.57 Safari/537.36');

    shuffle($agents);
    return $agents[0];
}

?>
