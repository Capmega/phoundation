<?php
/*
 * Internet library, contains all kinds of internet related functions
 *
 * These functions do not have a prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <license@capmega.com>
 */



/*
 * Get IPv4 from IPv6
 * Kindly provided by http://stackoverflow.com/questions/12435582/php-serverremote-addr-shows-ipv6
 * Rewritten for use in Base by Sven Oostenbrink
 */
function ip_v6_v4($ipv6){
    try{
        /*
         * Known prefix
         */
        $v4mapped_prefix_hex = '00000000000000000000ffff';
        $v4mapped_prefix_bin = hex2bin($v4mapped_prefix_hex);

        /*
         * Parse
         */
        $addr     = $_SERVER['REMOTE_ADDR'];
        $addr_bin = inet_pton($addr);

        if($addr_bin === false){
            /*
             * IP Unparsable? How did they connect?
             */
            throw new OutOfBoundsException(tr('IP address ":ip" is invalid', array(':ip' => $ipv6)), 'invalid');
        }

        /*
         * Check prefix
         */
        if(substr($addr_bin, 0, strlen($v4mapped_prefix_bin)) == $v4mapped_prefix_bin){
            /*
             * Strip prefix
             */
            $addr_bin = substr($addr_bin, strlen($v4mapped_prefix_bin));
        }

        /*
         * Convert back to printable address in canonical form
         */
        $ipv4 = inet_ntop($addr_bin);
        return $ipv4;

    }catch(Exception $e){
        throw new OutOfBoundsException(tr('ip_v6_v4(): Failed'), $e);
    }
}



/*
 * Returns 4 if the specified (or if not specified, current) IP address is ipv4
 * Returns 6 if the specified (or if not specified, current) IP address is ipv6
 */
function detect_ip_version($version = null){
    try{
        if(!$version){
            $version = $_SERVER['REMOTE_ADDR'];
        }

        return strpos($version, ':') ? 6 : 4;

    }catch(Exception $e){
        throw new OutOfBoundsException(tr('detect_ip_version(): Failed'), $e);
    }
}



/*
 * Returns true if specified (or if not, current) IP address is ipv6
 */
function is_ipv6($version = null){
    try{
        return detect_ip_version($version) === 6;

    }catch(Exception $e){
        throw new OutOfBoundsException(tr('is_ipv6(): Failed'), $e);
    }
}



/*
 * Test if the specified port on the specified hostname or IP responds
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inet
 *
 * @param string $params[host] The hostname or IP to connect to
 * @param natural (1-65535) $params[port] The port on the specified hostname or IP to connect to
 * @param natural [null] $params[server] If specified, execute this from the specified server
 * @param natural (1-600) [5] $params[timeout] Time to wait for a connection. Defaults to 5 seconds
 * @param enum (tcp|udp) [tcp] The protocol to be used. Either "tcp" or "udp"
 * @param boolean [false] $params[exception] If specified true, and connection fails, don't return boolean false, throw an exception instead.
 * @return boolean True if the specified host / port responds, false if not
 */
function inet_test_host_port($params){
    try{
        array_ensure($params, 'host,port,server');
        array_default($params, 'timeout'  , 5);
        array_default($params, 'exception', false);
        array_default($params, 'protocol' , 'tcp');

        inet_validate_port($params['port']);
        inet_validate_host($params['host']);

        if(!is_natural($params['timeout']) or ($params['timeout'] > 600)){
            throw new OutOfBoundsException(tr('inet_test_host_port(): Specified timeout ":timeout"is invalid. It must be a natural number smaller than or equal to 600', array(':timeout' => $params['timeout'])), 'invalid');
        }

        $results = servers_exec($params['server'], array('ok_exitcodes' => '0,1',
                                                         'commands'     => array('nc', array('-zv', $params['host'], $params['port'], '-w', $params['timeout']))));

        $results = array_shift($results);
        $results = strtolower($results);

        if(str_exists($results, 'connection refused')){
            if($params['exception']){
                throw new OutOfBoundsException(tr('inet_test_host_port(): Failed to connect to specified host:port ":host:%port"', array(':host' => $params['host'], '%port' => $params['port'])), 'warning/failed');
            }

            return false;
        }

        if(str_exists($results, 'succeeded!')){
            /*
             * Yei!
             */
            return true;
        }

        /*
         * No idea what happened
         */
        throw new OutOfBoundsException(tr('inet_test_host_port(): Unknown output received from nc command, see exception data'), 'unknown', $results);

    }catch(Exception $e){
        throw new OutOfBoundsException(tr('inet_test_host_port(): Failed'), $e);
    }
}



/*
 * Setup a connection with the specified host:port, and return the initially received data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inet
 *
 * @param string $params The parameters for the telnet command
 * @param string $params[host] The hostname or IP to connect to
 * @param natural (1-65535) $params[port] The port on the specified hostname or IP to connect to
 * @param natural [null] $params[server] If specified, execute this from the specified server
 * @param natural (1-600) [1] $params[timeout] Time to wait for the remote connection to send output
 * @return string The data received from the remote server
 */
function inet_telnet($params){
    try{
        array_ensure($params, 'host,port,server');
        array_default($params, 'timeout', 1);

        inet_validate_port($params['port']);
        inet_validate_host($params['host']);

        $results = servers_exec($params['server'], array('ok_exitcodes' => 124,
                                                         'commands'     => array('telnet', array($params['host'], $params['port'], 'timeout' => $params['timeout']))));
        return $results;

    }catch(Exception $e){
        throw new OutOfBoundsException(tr('inet_telnet(): Failed'), $e);
    }
}



/*
 * Return a stripped domain name, no bullshit around it.
 */
function inet_get_domain($strip = array('www', 'dev', 'm')){
    try{
        if(in_array(Strings::until($_SERVER['HTTP_HOST'], '.'), array_force($strip))){
            return Strings::from($_SERVER['HTTP_HOST']);
        }

        return $_SERVER['HTTP_HOST'];

    }catch(Exception $e){
        throw new OutOfBoundsException(tr('inet_get_domain(): Failed'), $e);
    }
}



/*
 * Get subdomain from specified domain relative to the $_SESSION['domain']
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inet
 * @version 2.8.21: Added documentation, improved function
 *
 * @param null string $domain The entire domain to test
 * @param null string $root The root part of the domain to test
 * @param null list $ignore_start If specified, ignore all subdomains that start with the specified string
 * @return boolean string The subdomain, false if there is none, or the found subdomain should be ignored
 */
function inet_get_subdomain($domain = null, $root = null, $ignore_start = 'cdn,api'){
    global $_CONFIG;

    try{
        if(!$domain){
            $domain = $_SERVER['HTTP_HOST'];
        }

        if(!$root){
            $root = $_CONFIG['domain'];
        }

        $len = mb_strlen($root);

        if(substr($domain, -$len, $len) !== $root){
            throw new OutOfBoundsException(tr('inet_get_subdomain(): Specified $domain (Fully Qualified Domain Name) ":domain" does not end with the root domain ":root"', array(':domain' => $domain, ':root' => $root)), 'not-exists');
        }

        $subdomain = urldecode(Strings::until($domain, '.'.$root, 0, 0, true));

        if($subdomain){
            if($ignore_start){
                $ignore_start = array_force($ignore_start);

                foreach($ignore_start as $value){
                    if(substr($subdomain, 0, strlen($value)) == $value){
                        return false;
                    }
                }
            }

        }else{
            /*
             * There is no sub domain
             */
            $subdomain = false;
        }


        return $subdomain;

    }catch(Exception $e){
        throw new OutOfBoundsException(tr('inet_get_subdomain(): Failed'), $e);
    }
}



/*
 * Ensure that the specified URL has a (or the specified) protocol
 */
function ensure_protocol($url, $protocol = 'http', $force = false){
    if(preg_match('/^(\w{2,6}):\/\//i', $url, $matches)){
        /*
         * The URL has a protocol
         */
        if($force and ($matches[1] != $protocol)){
            /*
             * It has not the protocol it should have, force protocol
             */
            return $protocol.'://'.Strings::from($url, '://');
        }

        return $url;
    }

    /*
     * The URL does not have a protocol. Add one now.
     */
    if(substr($url, 0, 2) == '//'){
        return $protocol.':'.$url;

    }else{
        return $protocol.'://'.$url;
    }
}



/*
 * Add specified query to the specified URL and return
 */
function inet_add_query($url){
    try{
        $queries = func_get_args();
        unset($queries[0]);

        if(!$queries){
            throw new OutOfBoundsException(tr('inet_add_query(): No queries specified'), 'not-specified');
        }

        foreach($queries as $query){
            if(!$query) continue;

            if(is_string($query) and strstr($query, '&')){
                $query = explode('&', $query);
            }

            if(is_array($query)){
                foreach($query as $key => $value){
                    if(is_numeric($key)){
                        /*
                         * $value should contain key=value
                         */
                        $url = inet_add_query($url, $value);

                    }else{
                        $url = inet_add_query($url, $key.'='.$value);
                    }
                }

                continue;
            }

            if($query === true){
                $query = $_SERVER['QUERY_STRING'];
            }

            if($query[0] === '-'){
                /*
                 * Remove this query instead of adding it
                 */
                $url = preg_replace('/'.substr($query, 1).'/', '', $url);
                $url = str_replace('&&'                      , '', $url);
                $url = str_ends_not($url                     , '?');

                continue;
            }

            $url = str_ends_not($url, '?');

            if(!preg_match('/.+?=.*?/', $query)){
                throw new OutOfBoundsException(tr('inet_add_query(): Invalid query ":query" specified. Please ensure it has the "key=value" format', array(':query' => $query)), 'invalid');
            }

            $key = Strings::until($query, '=');

            if(strpos($url, '?') === false){
                /*
                 * This URL has no query yet, begin one
                 */
                $url .= '?'.$query;

            }elseif(strpos($url, $key.'=') !== false){
                /*
                 * The query already exists in the specified URL, replace it.
                 */
                $replace = Strings::cut(($url, $key.'=', '&');
                $url     = str_replace($key.'='.$replace, $key.'='.Strings::from($query, '='), $url);

            }else{
                /*
                 * Append the query to the URL
                 */
                $url = str_ends($url, '&').$query;
            }
        }

        return $url;

    }catch(Exception $e){
        throw new OutOfBoundsException('inet_add_query(): Failed', $e);
    }
}



/*
 * Add specified query to the specified URL and return
 */
function url_remove_keys($url, $keys){
    try{
        $query = Strings::from($url , '?');
        $url   = Strings::until($url, '?');
        $query = explode('&', $query);

        foreach($query as $id => $kv){
            foreach(array_force($keys) as $key){
                if(Strings::until($kv, '=') == $key){
                    unset($query[$id]);

                    /*
                     * Don't break in case the specified key exists twice in the URL (might happen somehow)
                     */
                }
            }
        }

        if($query){
            return $url.'?'.implode('&', $query);
        }

        return $url;

    }catch(Exception $e){
        throw new OutOfBoundsException('url_remove_keys(): Failed', $e);
    }
}



/*
 * Return information about a domain
 *
 * See http://en.wikipedia.org/wiki/List_of_DNS_record_types for more information
 */
// :TODO: OBSOLETE, SEE DIG LIBRARY FOR NEW FUNCTIONS
function inet_dig($domain, $section = false){
    try{
        $data = safe_exec(array('commands' => array('dig', array(cfm($domain), 'ANY'))));
        $data = implode("\n", $data);
        $data = Strings::from($data, 'ANSWER: ');

        if(Strings::until($data, ',') == '0'){
            throw new OutOfBoundsException(tr('inet_dig(): Specified domain ":domain" was not found', array(':domain' => $domain)), 'not-exists');
        }

        $data   = Strings::cut(($data, "ANSWER SECTION:\n", "\n;;");
        $retval = array();

        if($section){
            /*
             * If specific sections were requested,
             * then store those lowercased in an array for easy lookup
             */
            $section = strtolower(str_force($section));
            $section = array_flip(array_force($section));
        }

        /*
         * Get A record
         */
        if(!$section or isset($section['a'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+A\s+(\d+\.\d+\.\d+\.\d+)/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['A'] = $matches[1];
            }
        }

        /*
         * Get MX record
         */
        if(!$section or isset($section['mx'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+MX\s+(.+?)\n/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['MX'] = $matches[1];
            }
        }

        /*
         * Get TXT record
         */
        if(!$section or isset($section['txt'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+TXT\s+(.+?)\n/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['TXT'] = $matches[1];
            }
        }

        /*
         * Get SOA record
         */
        if(!$section or isset($section['soa'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+SOA\s+(.+?)\n/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['SOA'] = $matches[1];
            }
        }

        /*
         * Get CNAME record
         */
        if(!$section or isset($section['cname'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+CNAME\s+(\d+\.\d+\.\d+\.\d+)\n/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['CNAME'] = $matches[1];
            }
        }

        /*
         * Get NS record
         */
        if(!$section or isset($section['ns'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+NS\s+(.+?)\n/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['NS'] = $matches[1];
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new OutOfBoundsException('inet_dig(): Failed', $e);
    }
}



/*
 *
 */
function inet_get_client_data(){
    try{
        /*
         * Fetch user data
         * Get email, IPv4, IPv6, user_agent, reverse host, provider, longitude, latitude, referrer
         */
        load_libs('geoip');

        $client['ip']           = $_SERVER['REMOTE_ADDR'];
        $client['email']        = isset_get($_GET['email']);
        $client['user_agent']   = isset_get($_SERVER['HTTP_USER_AGENT']);
        $client['referrer']     = isset_get($_SERVER['HTTP_REFERER']);
        $client['reverse_host'] = gethostbyaddr($client['ip']);

        $geodata = geoip_get($client['ip']);

        $client['latitude']  = isset_get($geodata['latitude']);
        $client['longitude'] = isset_get($geodata['longitude']);

        //if(empty($client['email']) and $_CONFIG['production']){
        //    header("HTTP/1.0 404 Not Found");
        //    die('404 - Not Found');
        //}



        /*
         * Detect browser
         */
        $oses = array('mac'     => 'desktop',
                      'linux'   => 'desktop',
                      'android' => 'mobile',
                      'iphone'  => 'mobile',
                      'windows' => 'desktop');

        if(empty($_SESSION['client'])){
            $user_agent = strtolower($client['user_agent']);
            $browsers   = array('chrome',
                                'firefox',
                                'msie 10',
                                'msie 9',
                                'msie 8',
                                'msie 7',
                                'opera');

            foreach($browsers as $browser){
                if(strstr($user_agent, $browser)){
                    $client['browser'] = $browser;
                    break;
                }
            }

            if(empty($client['browser'])){
                $client['browser'] = 'unknown';
            }

            /*
             * Detect operating system
             */
            $user_agent = strtolower($client['user_agent']);

            foreach($oses as $os => $platform){
                if(strstr($user_agent, $os)){
                    $client['os']       = $os;
                    $client['platform'] = $platform;
                    break;
                }
            }

        }else{
            /*
             * Get the information from $_SESSION[client]
             */
            $client['browser']  = strtolower(isset_get($_SESSION['client']['info']['browser']));
            $client['os']       = strtolower(isset_get($_SESSION['client']['info']['platform']));
            $client['platform'] = (empty($_SESSION['client']['info']['ismobiledevice']) ? 'desktop' : 'mobile');
        }

        if(empty($client['os'])){
            $client['os']       = 'unknown';
            $client['platform'] = 'unknown';
        }

        return $client;

    }catch(Exception $e){
        throw new OutOfBoundsException('inet_get_client_data(): Failed', $e);
    }
}



/*
 * Returns the specified port if it is valid, else it causes an exception. By default, system ports (< 1024)) are NOT valid
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inet
 * @exception BException If the specified port is not valid
 *
 * @param natural $port
 * @return The specified port, if valid
 */
function inet_validate_port($port, $lowest = 1025){
    try{
        if(!is_natural($port, $lowest) or ($port > 65535)){
            throw new OutOfBoundsException(tr('inet_validate_port(): Specified port ":port" is invalid', array(':port' => $port)), 'validation');
        }

        return $port;

    }catch(Exception $e){
        throw new OutOfBoundsException('inet_validate_port(): Failed', $e);
    }
}



/*
 * Returns the specified IP if it is valid, else it causes an exception. By default, empty values are allowed
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inet
 * @exception BException If the specified ip is not valid
 *
 * @param natural $ip
 * @param boolean $allow_empty
 * @return The specified ip, if valid
 */
function inet_validate_host($host, $allow_all = true, $exception = true){
    try{
        /*
         * Maybe its a valid IP?
         */
        $valid = inet_validate_ip($host, $allow_all, false);

        if(!$valid){
            return $valid;
        }

        if(!filter_var($host, FILTER_VALIDATE_DOMAIN)){
            throw new OutOfBoundsException(tr('inet_validate_host(): Specified host ":host" is invalid', array(':host' => $host)), 'validation');
        }

        return $host;

    }catch(Exception $e){
        throw new OutOfBoundsException('inet_validate_host(): Failed', $e);
    }
}



/*
 * Returns the specified IP if it is valid, else it causes an exception. By default, empty values are allowed
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inet
 * @exception BException If the specified ip is not valid
 *
 * @param natural $ip
 * @param boolean $allow_empty
 * @return The specified ip, if valid
 */
function inet_validate_ip($ip, $allow_all = true, $exception = true){
    try{
        if(!$ip){
            throw new OutOfBoundsException(tr('inet_validate_ip(): No ip specified'), 'validation');
        }

        if(($ip === '0.0.0.0') and !$allow_all){
            throw new OutOfBoundsException(tr('inet_validate_ip(): IP "0.0.0.0" is not allowed'), 'validation');
        }

        if(!filter_var($ip, FILTER_VALIDATE_IP)){
            throw new OutOfBoundsException(tr('inet_validate_ip(): Specified ip ":ip" is invalid', array(':ip' => $ip)), 'validation');
        }

        return $ip;

    }catch(Exception $e){
        if($exception){
            throw new OutOfBoundsException('inet_validate_ip(): Failed', $e);
        }

        return false;
    }
}



/*
 * Returns true if the specified port is available on the specified server, false otherwise
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inet
 * @see inet_validate_port()
 *
 * @param natural $port
 * @return
 */
function inet_port_available($port, $ip = '0.0.0.0', $server = null){
    load_libs('servers');

    try{
        $ip      = inet_validate_ip($ip);
        $port    = inet_validate_port($port);
        $results = servers_exec($server, array('ok_exitcodes' => '1',
                                               'commands'     => array('netstat', array('sudo' => true, '-peanut', 'connector' => '|'),
                                                                       'grep'   , array(':'.$port))));

        foreach($results as $result){
            preg_match_all('/ (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}):(\d{1,5}) /', $result, $matches);

            $found_ip   = $matches[1][0];
            $found_port = $matches[2][0];

            if($found_port == $port){
                if(!$ip or ($ip == $found_ip) or ($found_ip == '0.0.0.0')){
                    /*
                     * The port / IP or the port globally is already being listened on
                     */
                    return false;
                }
            }
        }

        return true;

    }catch(Exception $e){
        throw new OutOfBoundsException('inet_port_available(): Failed', $e);
    }
}



/*
 * Returns a random available port on the specified IP that can be listened on. If $ip is not specified, 0.0.0.0 (listen on all IPs) will be assumed
 *
 * The lowest port, by default, will be above the system ports (> 1024)
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inet
 * @exception BException if no available port could be found in $retries amount of retries
 * @see inet_port_available()
 *
 * @param string $ip
 * @return available port on the specified IP that is not being listened on yet
 */
function inet_get_available_port($ip = '0.0.0.0', $server = null, $lowest = 1025, $retries = 10){
    try{
        $count = 1;

        while($port = rand($lowest, 65535)){
            if(++$count > $retries){
                throw new OutOfBoundsException(tr('inet_get_available_port(): Failed to find an available port in ":retries" retries', array(':retries' => $retries)), 'failed');
            }

            if(inet_port_available($port, $ip, $server)){
                return $port;
            }
        }

        return $port;

    }catch(Exception $e){
        throw new OutOfBoundsException('inet_get_available_port(): Failed', $e);
    }
}



/*
 * OBSOLETE FUNCTIONS
 */



/*
 * Add specified query to the specified URL and return
 */
function url_add_query($url){
    try{
        return call_user_func_array('inet_add_query', func_get_args());

    }catch(Exception $e){
        throw new OutOfBoundsException('url_add_query(): Failed', $e);
    }
}
