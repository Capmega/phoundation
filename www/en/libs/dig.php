<?php
/*
 * Dig library
 *
 * This is a front-end to the dig command
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
 * @package dig
 *
 * @return void
 */
function dig_library_init() {
    try{
        if (!file_which('dig')) {
            throw new CoreException(tr('dig_library_init(): The "dig" command was not found. To install "dig" on ubuntu, please execute "sudo apt-get install dnsutils"'), 'not-exists');
        }


    }catch(Exception $e) {
        throw new CoreException('dig_library_init(): Failed', $e);
    }
}



/*
 * Install the dig command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package dig
 *
 * @param params $params
 * @return
 */
function dig_install($params) {
    try{
        $params['methods'] = array('apt-get' => array('commands'  => 'sudo apt-get install dnsutils'));
        return install($params);

    }catch(Exception $e) {
        throw new CoreException('dig_install(): Failed', $e);
    }
}



/*
 * Cleanup an dig output line
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package dig
 *
 * @param string $line The dig output line that contains record information;
 * @return string the cleaned up line data
 */
function dig_clean_line($line) {
    try{
        $line = trim($line);
        $line = str_replace("\t", ' ', $line);
        $line = Strings::from($line, ' ');
        $line = trim($line, ' ');

        return $line;

    }catch(Exception $e) {
        throw new CoreException('dig_clean_line(): Failed', $e);
    }
}



/*
 * Execute dig and return results
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package dig
 *
 * @param string $hostname;
 * @param string $$dns_server Should be an IP or DNS hostname. If specified, have dig request this information from this specified DNS server
 * @return array IP's reported by dig
 */
function dig($hostname, $command, $dns_server = null) {
    try{
        $results = safe_exec(array('commands' => array('dig', array($command, ($dns_server ? '@'.$dns_server : ''), $hostname))));
        $start   = false;
        $stop    = false;
        $retval  = array();

        foreach($results as $result) {
            if (strstr($result, 'ANSWER SECTION:')) {
                $start = true;
                continue;
            }

            if (!$start) {
                continue;
            }

            if (!$result) {
                break;
            }

            $result = dig_clean_line($result);
            $ttl    = (integer) Strings::until($result, ' ');
            $ip     = Strings::fromReverse($result, ' ');

            $retval[] = array('ttl' => $ttl,
                              'ip'  => $ip);
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('dig(): Failed', $e);
    }
}



/*
 * Get A records for the specified hostname
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package dig
 *
 * @param string $hostname;
 * @param string $$dns_server Should be an IP or DNS hostname. If specified, have dig request this information from this specified DNS server
 * @return array IP's reported by dig
 */
function dig_a($hostname, $dns_server = null) {
    try{
        return dig('', $hostname, $dns_server);

    }catch(Exception $e) {
        throw new CoreException('dig_a(): Failed', $e);
    }
}



/*
 * Get A records for the specified hostname
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package dig
 *
 * @param string $hostname;
 * @param string $$dns_server Should be an IP or DNS hostname. If specified, have dig request this information from this specified DNS server
 * @return array IP's reported by dig
 */
function dig_mx($hostname, $dns_server = null) {
    try{
        return dig('MX', $hostname, $dns_server);

    }catch(Exception $e) {
        throw new CoreException('dig_mx(): Failed', $e);
    }
}
?>
