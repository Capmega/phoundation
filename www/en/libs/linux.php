<?php
/*
 * Linux library
 *
 * This is the Linux library. This library contains functions to execute operating system functions on as many as possible Linux distributions
 * This library is a front end to other libraries that have specific implementations for the required functions on their specific operating systems
 * Examples of these other libraries are ubuntu, ubuntu1604, redhad, fedora, fedora25, etc
 *
 * NOTE: These functions should NOT be called directly, they should be called by functions from the "os" library!
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package linux
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @return void
 */
function linux_library_init(){
    try{
        load_libs('servers');

    }catch(Exception $e){
        throw new BException('linux_library_init(): Failed', $e);
    }
}



/*
 * Gets and returns SSH server AllowTcpForwarding configuration for the specified server
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server
 * @return boolean True if AllowTcpForwarding is configured, False if not
 */
function linux_get_ssh_tcp_forwarding($server){
    try{
        $server   = servers_get($server);
        $results  = servers_exec($server, array('commands' => array('sshd', array('-T', 'redirect' => '2> /dev/null', 'connector' => '|'),
                                                                    'grep', array('-i', 'allowtcpforwarding'))));
        $result   = array_shift($results);
        $result   = strtolower(trim($result));
        $result   = str_cut($result, ' ', ' ');

        switch($result){
            case 'yes';
                return true;

            case 'no';
                return false;

            default:
                throw new BException(tr('linux_get_ssh_tcp_forwarding(): Unknown result ":result" received from SSHD configuration on server ":server"', array(':server' => $server['domain'], ':result' => $result)), 'unknown');
        }

    }catch(Exception $e){
        throw new BException('linux_get_ssh_tcp_forwarding(): Failed', $e);
    }
}



/*
 * Enable SSH TCP forwarding on the specified linux server. The function makes a backup of the current SSH daemon configuration file, and update the current file to enable TCP forwarding/
 * For the moment, this function assumes that every linux distribution uses /etc/ssh/sshd_config for SSH daemon configuration, and that all use "AllowTcpForwarding no" or "AllowTcpForwarding yes"
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server
 * @param boolean $enable
 * @return array
 */
function linux_set_ssh_tcp_forwarding($server, $enable, $force = false){
    try{
        $server = servers_get($server);

        if(!$server['allow_sshd_modification'] and !$force){
            throw new BException(tr('linux_set_ssh_tcp_forwarding(): The specified server ":server" does not allow SSHD modifications', array(':server' => $server['domain'])), 'not-allowed');
        }

        $enable   = ($enable ? 'yes' : 'no');
        $results  = servers_exec($server, array('commands' => array('cp'     , array('sudo' => true, '-a', '/etc/ssh/sshd_config /etc/ssh/sshd_config~'.date_convert(null, 'Ymd-His'), 'connect' => '&&'),
                                                                    'sed'    , array('sudo' => true, '-iE', 's/AllowTcpForwarding \+\(yes\|no\)/AllowTcpForwarding '.$enable.'/gI', '/etc/ssh/sshd_config', 'connect' => '&'),
                                                                    'service', array('sudo' => true, 'ssh', 'restart'))));

        return $enable;

    }catch(Exception $e){
        throw new BException('linux_enable_ssh_tcp_forwarding(): Failed', $e);
    }
}



/*
 * Returns if the specified file exists on the specified server
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server
 * @param string $path
 * @return boolean True if the file exists, false if not
 */
function linux_file_exists($server, $path){
    try{
        $server  = servers_get($server);
        $results = servers_exec($server, array('ok_exitcodes' => '0,2',
                                               'commands'     => array('ls', array($path))));

        return true;

    }catch(Exception $e){
showdie($e);
        throw new BException('linux_file_exists(): Failed', $e);
    }
}



/*
 * Returns if the specified file exists on the specified server
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server
 * @param string $path
 * @return boolean True if the file exists, false if not
 */
function linux_scandir($server, $path){
    try{
        $server  = servers_get($server);
        $results = servers_exec($server, array('commands' => array('ls', array($path))));
        $result  = array_shift($results);
        $result  = strtolower(trim($result));

        return $result;

    }catch(Exception $e){
        throw new BException('linux_scandir(): Failed', $e);
    }
}



/*
 * Delete a file, weather it exists or not, without error, from the specified
 * server
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 * @note If the specified file pattern does not exist (doesn't match any files), no error will be thrown
 * @exception BException will be thrown if files, for whatever reasons, cannot be deleted
 *
 * @param mixed $server
 * @param string $path
 * @return void
 */
// :SECURITY: $pattern is NOT checked!!
function linux_file_delete($server, $patterns, $clean_path = false, $sudo = false){
    try{
        if(!$patterns){
            throw new BException('linux_file_delete(): No files or patterns specified');
        }

        $server = servers_get($server);

        foreach(array_force($patterns) as $pattern){
            servers_exec($server, array('commands' => array('rm', array_merge(array('sudo' => $sudo, '-rf'), $patterns))));

            if($clean_path){
                linux_file_clear_path(dirname($patterns));
            }
        }

    }catch(Exception $e){
        throw new BException('linux_file_delete(): Failed', $e);
    }
}



/*
 * Delete the path until directory is no longer empty on the specified server
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server
 * @param string $path
 * @return
 */
function linux_file_clear_path($server, $path){
    try{
        $server = servers_get($server);

        if(!linux_file_exists($server, $path)){
            /*
             * This section does not exist, jump up to the next section
             */
            return linux_file_clear_path($server, dirname($path));
        }

        if(!is_dir($path)){
            /*
             * This is a normal file. Delete it and continue with the directory above
             */
            unlink($path);

        }else{
            $files = linux_scandir($server, $path);

            foreach($files as $file){
                /*
                 * Skip . and ..
                 */
                if(($file == '.') or ($file == '..')) continue;

                $contents = true;
                break;
            }

            if($contents){
                /*
                 * Do not remove anything more, there is contents here!
                 */
                return true;
            }

            /*
             * Remove this entry and continue;
             */
            try{
                linux_file_execute_mode($server, dirname($path), (linux_is_writable(dirname($path)) ? false : 0770), function($path){
                    linux_file_delete($server, $path);
                });

            }catch(Exception $e){
                /*
                 * The directory WAS empty, but cannot be removed
                 *
                 * In all probability, a parrallel process added a new content
                 * in this directory, so it's no longer empty. Just register
                 * the event and leave it be.
                 */
                log_console(tr('linux_file_clear_path(): Failed to remove empty path ":path" on server ":server" with exception ":e"', array(':path' => $path, ':server' => $server['domain'], ':e' => $e)), 'failed');
                return true;
            }
        }

        /*
         * Go one entry up and continue
         */
        $path = str_runtil(unslash($path), '/');
        linux_file_clear_path($server, $path);

    }catch(Exception $e){
        throw new BException('linux_file_clear_path(): Failed', $e);
    }
}



/*
 * Returns true if the specified file is writable on the specified server with
 * its configured user
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server
 * @param string $file The file to be tested
 * @return boolean True if the specified file is writable, false if not
 */
function linux_is_writable($server, $file){
    try{
        $server  = servers_get($server);
        $results = servers_exec($server, array('commands' => array('test', array('-w', $path, 'connector' => '&&'),
                                                                   'echo', array('writable'))));
        $result  = array_shift($results);

        return $result;

    }catch(Exception $e){
        throw new BException('linux_is_writable(): Failed', $e);
    }
}



/*
 * Returns a list of PID's for the specified process names on the specified
 * server
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server
 * @param string $name The name of the process to be checked
 * @return array a list of all the process id's that were found
 */
function linux_pgrep($server, $name){
    try{
        $server  = servers_get($server);
        $results = servers_exec($server, array('ok_exitcodes' => '0,1',
                                               'commands'     => array('pgrep', array($name))));

        if(count($results) == 1){
            if(!current($results)){
                /*
                 * No process id's found
                 */
                return array();
            }
        }

        return $results;

    }catch(Exception $e){
        throw new BException('linux_pgrep(): Failed', $e);
    }
}



/*
 * Kill all processes with the specified process name on the specified server
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server
 * @param string $file The file to be tested
 * @return boolean True if the specified file is writable, false if not
 */
function linux_pkill($server, $process, $signal = null, $sudo = false, $verify_tries = 3, $check_timeout = 1, $sigkill = true){
    try{
        $server = servers_get($server);

        switch($signal){
            case 9:
                // FALLTHROUGH
            case 15:
                /*
                 * These are valid and supported signal
                 */
                break;

            case '':
                $signal = 15;
                break;

            default:
                throw new BException(tr('linux_pkill(): Unknown signal ":signal" specified', array(':signal' => $signal)), 'unknown');
        }

        /*
         * pkill returns 1 if no process name matched, so we can ignore that
         */
        $results = servers_exec($server, array('ok_exitcodes' => '0,1',
                                               'commands'     => array('pkill', array('sudo' => $sudo, '-'.$signal, $process))));
        $results = array_shift($results);

        if($results){
            /*
             * pkill returned some issue
             */
            throw new BException(tr('linux_pkill(): Failed to kill process ":process" with error ":e"', array(':process' => $process, ':e' => $results)), 'failed');
        }

        /*
         * Ensure that the progress is gone?
         */
        if(--$verify_tries > 0){
            sleep($check_timeout);

            $results = linux_pgrep($server, $process);

            if(!$results){
                /*
                 * Killed it softly
                 */
                return true;
            }

            return linux_pkill($server, $process, $signal, $sudo, $verify_tries, $check_timeout, $sigkill);
        }

        if($sigkill){
            /*
             * Verifications failed, now sigkill it
             * Sigkill it!
             */
            $result = linux_pkill($server, $process, 9, $sudo, false, $check_timeout, true);

            if($result){
                /*
                 * Killed it the hard way!
                 */
                return true;
            }
        }

        throw new BException(tr('linux_pkill(): Failed to kill process ":process" on server ":server"', array(':process' => $process, ':server' => $server['domain'])), 'failed');

    }catch(Exception $e){
        throw new BException('linux_pkill(): Failed', $e);
    }
}



/*
 * Return all system processes that match the specified filters on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param numeric $source_port
 * @param numeric $hostname
 * @param numeric $target_port
 * @param numeric $target_hostname
 * @return numeric PID of the found tunnel with the specified parameters, null if no tunnel was found
 */
function linux_list_processes($server, $filters){
    try{
        $filters = array_force($filters);
        $results = safe_exec(array('ok_exitcodes' => '0,1',
                                   'commands'     => array('ps'  , array('ax', 'connector' => '|'),
                                                           'grep', array_merge(array('--color=never', 'connector' => '|'), $filters),
                                                           'grep', array('--color=never', '-v', 'grep --color=never'))));
        $retval  = array();

        foreach($results as $key => $result){
            if(strstr($result, $command)){
                unset($results[$key]);
                continue;
            }

            $result       = trim($result);
            $pid          = str_until($result, ' ');
            $retval[$pid] = substr($result, 27);
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('linux_list_processes(): Failed', $e);
    }
}



/*
 * Check if the specified PID is available on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server The server on which the pid should be tested
 * @param natural $pid The PID to be tested
 * @return boolean True if the specified PID is available on the specified server, false otherwise
 */
function linux_pid($server, $pid){
    try{
        return linux_file_exists($server, '/proc/'.$pid);

    }catch(Exception $e){
        throw new BException('linux_pid(): Failed', $e);
    }
}



/*
 * Execute the netstat command on the specified server and return parsed output
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server The server on which the pid should be tested
 * @param natural $pid The PID to be tested
 * @return boolean True if the specified PID is available on the specified server, false otherwise
 */
function linux_netstat($server, $options){
    try{

        return linux_file_exists($server, 'netstat '.$parameters);

    }catch(Exception $e){
        throw new BException('linux_netstat(): Failed', $e);
    }
}



/*
 * Locates the specifed command on the specified server and returns it path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 * @version 2.0.5: Added function and documentation
 * @version 2.4.16: Added $whereis support
 *
 * @param mixed $server The server where this function will be executed
 * @param string $file The command searched for
 * @param boolean $whereis If set to true, instead of "which", "whereis" will be used
 * @return string The path of the specified file
 */
function linux_which($server, $command, $whereis = false){
    try{
        $result = servers_exec($server, array('ok_exitcodes' => '0,1',
                                              'commands'     => array(($whereis ? 'whereis' : 'which'), array($command))));

        $result = array_shift($result);

        return get_null($result);

    }catch(Exception $e){
        throw new BException('linux_which(): Failed', $e);
    }
}



/*
 * Ensures existence of specified path on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 * @version 2.4.10: Added function and documentation
 *
 * @param string $file The file to be unzipped
 * @return string The path of the specified file
 */
function linux_ensure_path($server, $path, $mode = null, $clear = false){
    global $_CONFIG;

    try{
        if(!$mode){
            $mode = $_CONFIG['fs']['dir_mode'];
        }

        if(!$path){
            throw new BException(tr('linux_ensure_path(): No path specified'), 'not-specified');
        }

        if($path[0] !== '/'){
            throw new BException(tr('linux_ensure_path(): Specified path ":path" is not absolute', array(':path' => $path)), 'invalid');
        }

        if(str_exists($path, '..')){
            throw new BException(tr('linux_ensure_path(): Specified path ":path" contains parent path sections', array(':path' => $path)), 'invalid');
        }

        if(substr_count($path, '/') < 3){
            if(substr($path, 0, 5) !== '/tmp/'){
                throw new BException(tr('linux_ensure_path(): Specified path ":path" is not deep enough. Top level- and second level directories cannot be ensured except in /tmp/', array(':path' => $path)), 'invalid');
            }
        }

        /*
         * Ensure this is not executed on ROOT or part of ROOT
         */
        $server = servers_get($server);

        switch($server['domain']){
            case '':
                // FALLTHROUGH
            case 'localhost':
                try{
                    if(str_exists(ROOT, linux_realpath($server, $path))){
                        throw new BException(tr('linux_ensure_path(): Specified path ":path" is ROOT or parent of ROOT', array(':path' => $path)), 'invalid');
                    }

                }catch(Exception $e){
                    if($e->getRealCode() !== 'not-exists'){
                        /*
                         * If the target path would not exist we'd be okay
                         */
                        throw $e;
                    }
                }
        }

        /*
         * Set mode if required so
         */
        if($mode){
            $arguments = array('-p', $path);

        }else{
            $arguments = array('-m', $mode, '-p', $path);
        }

        /*
         * Ensure that the specified path is cleared if specified so
         */
        if($clear){
            servers_exec($server, array('commands' => array('rm'   , array($path, '-rf'),
                                                            'mkdir', $arguments)));

        }else{
            servers_exec($server, array('commands' => array('mkdir', $arguments)));
        }

        return $path;

    }catch(Exception $e){
        throw new BException('linux_ensure_path(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 * @version 2.4.10: Added function and documentation
 *
 * @param string $file The file to be unzipped
 * @return string The path of the specified file
 */
function linux_rename($server, $path, $source, $target, $sudo = false){
    try{

    }catch(Exception $e){
        throw new BException('linux_rename(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 * @version 2.4.10: Added function and documentation
 *
 * @param string $file The file to be unzipped
 * @return string The path of the specified file
 */
function linux_copy($server, $source, $target, $sudo = false){
    try{

    }catch(Exception $e){
        throw new BException('linux_copy(): Failed', $e);
    }
}



/*
 * Delete the specified path on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 * @version 2.4.10: Added function and documentation
 *
 * @param mixed $file The file to be unzipped
 * @param mixed $patterns The file patterns to be deleted. An array with multiple patterns can be specified
 * @param boolean $sudo If set to true, the rm command will be executed with sudo
 * @param boolean $clean_path If set to true, the rm command will cleanup all parent paths as well if they're empty
 * @return string The path of the specified file
 */
function linux_delete($server, $patterns, $sudo = false, $clean_path = true){
    try{
notify('UNDER CONSTRUCTION! linux_delete() does not yet have support for $clean_path');
        if(!is_array()){
            $patterns = array($patterns);
        }

        if($sudo){
            $patterns['sudo'] = $sudo;
        }

        servers_exec($server, array('commands' => array('rm', $patterns)));

        if($clean_path){
        }

    }catch(Exception $e){
        throw new BException('linux_delete(): Failed', $e);
    }
}



/*
 * Unzip the specified file un the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 * @version 2.4.10: Added function and documentation
 *
 * @param string $file The file to be unzipped
 * @param boolean $remove If set to true, the specified zip file will be removed after the unzip action
 * @return string The path of the specified file
 */
function linux_unzip($server, $file, $remove = true){
    try{
under_construction('Move this to compress_unzip()');
        $filename = filename($file);
        $filename = str_runtil($file, '.');
        $path     = TMP.$filename.'/';

        linux_ensure_path($server, $path);

        if($move){
            linux_rename($server, $file, $path.$filename);

        }else{
            linux_copy($server, $file, $path.$filename);
        }

        /*
         * Unzip and
         */
        servers_exec($server, array('commands' => array('cd'    , array($path),
                                                        'gunzip'. array($filename))));
        linux_delete($server, $path.$filename);

        return $path;

    }catch(Exception $e){
        if(!linux_file_exists($file)){
            throw new BException(tr('linux_unzip(): The specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
        }

        throw new BException('linux_unzip(): Failed', $e);
    }
}



/*
 * Download the specified single file to the specified path on the specified server
 *
 * If the path is not specified then by default the function will download to the TMP directory; ROOT/data/tmp
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 * @see download()
 * @version 2.4.10: Added function and documentation
 *
 * @param mixed $server
 * @param string $url The URL of the file to be downloaded
 * @param mixed $section If set to false, will return the contents of the downloaded file instead of the target filename. As the caller function will not know the exact filename used, the target file will be deleted automatically! If set to a string
 * @param null function $callback If specified, download will execute this callback with either the filename or file contents (depending on $section)
 * @return string The downloaded file
 */
function linux_download($server, $url, $section = false, $callback = null){
    try{
        $file = str_from($url, '://');
        $file = str_rfrom($url, '/');
        $file = str_until($file, '?');

        if($section){
            if(!is_string($section)){
                throw new BException(tr('linux_download(): Specified section should either be false or a string. However, it is not false, and is of type ":type"', array(':type' => gettype($section))), 'invalid');
            }

            $file = TMP.$section.'/'.$file;

        }else{
            $file = TMP.$file;
        }

        load_libs('wget');
        linux_ensure_path(TMP.$section, 0770, true);

        wget(array('domain' => $server,
                   'url'    => $url,
                   'file'   => $file));

        if(!$section){
            /*
             * No section was specified, return contents of file instead.
             */
            if($callback){
                /*
                 * Execute the callbacks before returning the data
                 */
                $callback($file);
                file_delete($file);
            }

            return $file;
        }

        /*
         * Do not return the filename but the file contents instead
         * When doing this, automatically delete the file in question, since
         * the caller will not know the exact file name used
         */
        $retval = file_get_contents($file);
        file_delete($file);

        if($callback){
            $callback($retval);
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('linux_download(): Failed', $e);
    }
}



/*
 * Install the specified package on the linux operating system
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 * @version 2.4.11: Added function and documentation
 *
 * @param string $package
 * @return void
 */
function linux_install_package($server, $package){
    try{
//        $os = linux_detect_os($server);
        $os['distribution'] = 'ubuntu-server';

        switch($os['distribution']){
            case 'debian':
                // FALLTHROUGH
            case 'ubuntu':
                // FALLTHROUGH
            case 'ubuntu-server':
                // FALLTHROUGH
            case 'kubuntu':
                // FALLTHROUGH
            case 'lubuntu':
                // FALLTHROUGH
            case 'xubuntu':
                // FALLTHROUGH
            case 'edubuntu':
                // FALLTHROUGH
            case 'mint':
                load_libs('ubuntu');
                return ubuntu_install_package($package, $server);

            case 'redhat':
                // FALLTHROUGH
            case 'centos':
                // FALLTHROUGH
            case 'fedora':
                load_libs('redhat');
                return redhat_install_package($package, $server);
                break;

            default:
                throw new BException(tr('linux_install_package(): The detected operating system ":distribution" is not supported', array(':distribution' => $os['distribution'])), 'not-supported');
        }

    }catch(Exception $e){
        throw new BException('linux_install_package(): Failed', $e);
    }
}



/*
 * Detect the operating system on specified server and return the data
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 * @version 2.4.11: Added function and documentation
 *
 * @param mixed $server
 * @return array The operating system parameters
 */
function linux_detect_os($server){
    try{
        $results = server_exec($server, array('commands' => array('cat'  , array('/etc/issue'),
                                                                  'uname', array('-a'))));
showdie($results);

    }catch(Exception $e){
        throw new BException('linux_detect_os(): Failed', $e);
    }
}



/*
 * Get the real path for the specified path on the target server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 * @see realpath()
 * @version 2.4.20: Added function and documentation
 *
 * @param mixed $server
 * @param string $path
 * @return string The real path on the specified server
 */
function linux_realpath($server, $path){
    try{
        $results = servers_exec($server, array('commands' => array('realpath', array($path))));
        $results = array_shift($results);

        return $results;

    }catch(Exception $e){
        throw new BException('linux_realpath(): Failed', $e);
    }
}



/*
 * Execute the service command on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 * @version 2.4.22: Added function and documentation
 *
 * @param mixed $server
 * @param string $service
 * @param string $action
 * @return void()
 */
function linux_service($server, $service, $action){
    try{
        servers_exec($server, array('commands' => array('service', array('sudo' => true, $service, $action))));

    }catch(Exception $e){
        throw new BException('linux_service(): Failed', $e);
    }
}



/*
 * Return the current working directory (CWD) for the specified process id (PID)
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 * @version 2.5.2: Added function and documentation
 *
 * @param natural The PID for which the CWD is required
 * @return string The CWD for the specified PID if it exist
 */
function linux_get_cwd($pid){
    try{
        if(!is_natural($pid) or ($pid > 65535)){
            throw new BException(tr('linux_get_cwd(): Specified PID ":pid" is invalid', array(':pid' => $pid)), 'invalid');
        }

        $results = safe_exec(array('commands' => array('readlink', array('sudo' => true, '-e', '/proc/'.$pid.'/cwd'))));
        $results = array_pop($results);

        return $results;

    }catch(Exception $e){
        throw new BException('linux_get_cwd(): Failed', $e);
    }
}
?>
