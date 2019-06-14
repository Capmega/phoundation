<?php
/*
 * Check-disk library
 *
 * This library contains functions to check filesystems for issues
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package check-disk
 */



/*
 * Check the disk full status under the specified path (defaults to this projects) and execute callback if limit is passed
 *
 * This function will check the disk where the specified path is mounted for available and total space and compare those two to the specified limits. If one or multiple limits are passed, the callback function will be executed
 *
 * By default, the callback function will empty the cache, tmp, and log directories
 *
 * Limits can be specified either by an absolute number with or without a KMGTP suffix (e.g. 500000, 100K, 50M, 300G, etc), or a % (e.g. 10%) or a combination of an absolute number and % separated by a comma (e.g. 10%,500M)
 *
 * If a % is specified, and the disk where the specified path is mounted has less than that % available, the callback function will execute
 *
 * If an absolute number is specified, and the disk where the specified path is mounted has less than that number in bytes available, the callback function will execute
 *
 * If both are specified, and either one of them has less than the specified limit, both will execute
 *
 * The callback function signature is $params[callback](integer $total, integer $available, integer $limit_percentage integer $limit_bytes) where $total is the total size for the filesystem on the specified path, $available is the amount of bytes available, $limit_percentage is the caller specified minimum percentage, and $limit_bytes is the caller specified minimum amount of bytes
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package check-disk
 * @version 2.0.6: Added function and documentation
 * @version 2.4.8: Moved function from system to check-disk library
 *
 * @param params $params The function parameters
 * @param $params[path] The directory to check. Defaults to ROOT
 * @param null string $params[bytes] =$_CONFIG[check_disk][bytes] The amount of minimal available bytes limit that should not be crossed. Must be an absolute number in bytes with or without a KMGTP suffix (e.g. 500000, 100K, 50M, 300G, etc)
 * @param null string $params[percentage] =$_CONFIG[check_disk][percentage] The percentage of minimal available bytes limit that should not be crossed. Must be in the form of N% (e.g. 10%)
 * @param function $params[callback] The callback function to execute if disk full limits have been crossed. Defaults to a default function that clears cache, tmp, and log directories
 * @return null mixed If disk usage did not cross the specified limits null, else the output of the callback function
 */
function check_disk($params = null){
    global $_CONFIG;

    try{
        array_ensure($params, 'percentage,bytes');
        array_default($params, 'bytes'     , $_CONFIG['check_disk']['bytes']);
        array_default($params, 'percentage', $_CONFIG['check_disk']['percentage']);

        if(empty($params['callback'])){
            /*
             * Perform default recovery actions
             */
            $params['callback'] = function($total, $available, $percentage, $bytes){
                file_delete(ROOT.'data/tmp'  , ROOT.'data/');
                file_delete(ROOT.'data/cache', ROOT.'data/');
                file_delete(ROOT.'data/log'  , ROOT.'data/');

                /*
                 * Regenerate the paths to ensure that they are available
                 */
                file_ensure_path(ROOT.'data/tmp');
                file_ensure_path(ROOT.'data/cache');
                file_ensure_path(ROOT.'data/log');

                notify(array('code'    => 'low-disk',
                             'groups'  => 'developers',
                             'title'   => tr('Low disk event encountered'),
                             'message' => tr('check_disk(): Low diskspace event encountered, ":available available from :total total" detected with limits set to ":bytes bytes / :percentage%". Executing default callback function which will delete projects\' tmp, cache, and log directories', array(':available' => $available, ':total' => $total, ':bytes' => $bytes, ':percentage' => $percentage))));
            };
        }

        if(empty($params['path'])){
            $params['path'] = ROOT;
        }

        if(!file_exists($params['path'])){
            throw new BException(tr('check_disk(): The specified path ":path" does not exist', array(':path' => $params['path'])), 'not-exists');
        }

        $total      = disk_total_space($params['path']);
        $available  = disk_free_space($params['path']);
        $bytes      = $total - $available;
        $percentage = (($available / $total) * 100);

        if($percentage < $params['percentage']){
            $execute = true;
        }

        if($bytes < $params['bytes']){
            $execute = true;
        }

        if(isset($execute)){
            notify(array('code'    => 'low-disk',
                         'groups'  => 'developers',
                         'title'   => tr('Low disk event encountered'),
                         'message' => tr('check_disk(): Low diskspace event encountered, ":available available from :total total" detected with limits set to ":bytesbytes/:percentage%". Executing callback function', array(':available' => $available, ':total' => $total, ':bytes' => $params['bytes'], ':percentage' => $params['percentage']))));

            return $params['callback']($total, $available, $params['percentage'], $bytes);
        }

        return null;

    }catch(Exception $e){
        throw new BException(tr('check_disk(): Failed'), $e);
    }
}
?>
