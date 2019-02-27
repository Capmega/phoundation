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
        $commands = 'sshd -T 2> /dev/null | grep -i allowtcpforwarding';
        $results  = servers_exec($server, $commands);
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
        $commands = 'sudo cp -a /etc/ssh/sshd_config /etc/ssh/sshd_config~'.date_convert(null, 'Ymd-His').' && sudo sed -iE \'s/AllowTcpForwarding \+\(yes\|no\)/AllowTcpForwarding '.$enable.'/gI\' /etc/ssh/sshd_config && sudo service ssh restart';
        $results  = servers_exec($server, $commands);

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
        $results = servers_exec($server, 'ls '.$path, false, null, 2);

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
        $results = servers_exec($server, 'ls '.$path);
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
            servers_exec($server, ($sudo ? 'sudo ' : '').'rm -rf '.$pattern);

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
        $results = servers_exec($server, 'test -w "'.$path.'" && echo "writable"');
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
        $results = servers_exec($server, 'pgrep '.$name, false, null, 1);

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
        $results = servers_exec($server, ($sudo ? 'sudo ' : '').'pkill -'.$signal.' '.$process, false, null, 1);
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

        foreach($filters as &$filter){
            $filter = trim($filter);

            if($filter[0] == '-'){
                $filter = '\\\\'.$filter;
            }

            $filter = '"'.$filter.'"';
        }

        unset($filter);

        $filters = implode(' | grep --color=never ', $filters);
        $command = 'ps ax | grep -v "grep" | grep --color=never '.$filters;
        $results = servers_exec($server, $command, false, null, '0,1');
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
        $result = servers_exec($server, ($whereis ? 'whereis' : 'which').' "'.$command.'"', false, null, '0,1');
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
            throw new BException(tr('linux_ensure_path(): Specified path ":path" is not deep enough. Top level- and second level directories cannot be ensured', array(':path' => $path)), 'invalid');
        }

        /*
         * Ensure this is not executed on THIS path
         */
        $server = servers_get($server);

        switch($server['domain']){
            case '':
                // FALLTHROUGH
            case 'localhost':
                if(str_exists(ROOT, $path)){
                    throw new BException(tr('linux_ensure_path(): Specified path ":path" is ROOT or parent of ROOT', array(':path' => $path)), 'invalid');
                }
        }

        if($clear){
showdie($path);
            server_exec($server, 'rm "'.$path.'" -rf; mkdir '.($mode ? ' -m "'.$mode.'"' : '').' -p "'.$path.'"');

        }else{
showdie($path);
            server_exec($server, 'mkdir '.($mode ? ' -m "'.$mode.'"' : '').' -p "'.$path.'"');
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
 * @param string $file The file to be unzipped
 * @return string The path of the specified file
 */
function linux_delete($server, $patterns, $clean_path = false, $sudo = false){
    try{

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
        servers_exec($server, 'cd '.$path.'; gunzip "'.$filename.'"');
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

        load_libs('linux');
        linux_ensure_path(TMP.$section, 0770, true);
        safe_exec('wget -q -O '.$file.' - "'.$url.'"');

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
            case 'ubuntu-server':
                // FALLTHROUGH
            case 'ubuntu':
                // FALLTHROUGH
            case 'kubuntu':
                // FALLTHROUGH
            case 'lubuntu':
                // FALLTHROUGH
            case 'xubuntu':
                // FALLTHROUGH
            case 'edubuntu':
                // FALLTHROUGH
            case 'debian':
                // FALLTHROUGH
            case 'mint':
                load_libs('apt');
                return apt_install($package);

            case 'redhat':
                // FALLTHROUGH
            case 'centos':
                // FALLTHROUGH
            case 'fedora':
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
        $results = server_exec($server, 'cat /etc/issue; echo; uname -a');
showdie($results);

    }catch(Exception $e){
        throw new BException('linux_detect_os(): Failed', $e);
    }
}
?>
