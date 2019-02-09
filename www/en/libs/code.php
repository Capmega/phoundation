<?php
/*
 * Code library
 *
 * This library contains functions to assist with code management
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package code
 * @note Version lines are all tagged versions that share the same MAJOR.MINOR version numbers. E.g. 2.2.0, 2.2.1, 2.2.2, 2.2.3 are all part of the 2.2 code line

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
 * @package code
 * @version 2.0.5: Added function and documentation
 *
 * @return void
 */
function code_library_init(){
    try{
        load_libs('git');

    }catch(Exception $e){
        throw new BException('code_library_init(): Failed', $e);
    }
}



/*
 * Locate the local phoundation project and return its path
 *
 * This function will look for the phoundation system path and return it. The script will first search in the current directory parrallel to ROOT, then one directory higher, then in /var/www/html, then in ~/projects/
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @version 2.2.0: Added function and documentation
 *
 * @param string $version The version to get the version line from
 * @return string the version line for the specified version
 */
function code_locate_phoundation(){
    static $found;

    try{
        if(!$found){
            $paths = array(ROOT.'../phoundation/',
                           ROOT.'../../phoundation/',
                           '/var/www/html/phoundation/');

            $home = getenv('HOME');

            if($home){
                $paths[] = slash($home).'projects/phoundation/';
            }

            foreach($paths as $path){
                if(file_exists($path)){
                    /*
                     * Found something with the correct name!
                     */
                    if(git_is_repository($path)){
                        /*
                         * Its a git repository too!
                         * Check if its really the phoundation repository by
                         * ensuring the first phoundation commit hash is
                         * available
                         */
                        try{
                            $result = git_show('290071b81e7bebab9c43aa1fd3a8b691ca1f9695', $path, array('check' => true));
                            $found  = realpath($path);

                        }catch(Exception $e){
                            if($e->getCode() == 128){
                                /*
                                 * The phoundation initial commit does not
                                 * exist, this is not the phoundation project!
                                 *
                                 * Continue looking
                                 */
                                continue;
                            }

                            throw $e;
                        }
                    }
                }
            }

        }

        if(!$found){
            throw new BException(tr('code_locate_phoundation(): Failed to find the phoundation project in any of the search paths ":paths"', array(':paths' => $paths)), 'warning/not-exists');
        }

        return $found;

    }catch(Exception $e){
        throw new BException('code_locate_phoundation(): Failed', $e);
    }
}



/*
 * Download objects and refs from another repository on the specified path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_locate_phoundation()
 * @see git_fetch()
 * @version 2.2.0: Added function and documentation
 *
 * @param null params $params The fetch parameters
 * @return array Return the available versions for the git project
 */
function code_phoundation_fetch($params = null){
    try{
        $path   = code_locate_phoundation();
        $branch = git_fetch($path, $params);

        return $branch;

    }catch(Exception $e){
        throw new BException('code_phoundation_fetch(): Failed', $e);
    }
}



/*
 * Set or return the current branch for the phoundation project
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_locate_phoundation()
 * @see git_branch()
 * @version 2.2.0: Added function and documentation
 *
 * @param null string $branch If specified, set the phoudation project to this branch
 * @return array Return the available versions for the git project
 */
function code_phoundation_branch($branch = null){
    try{
        $path   = code_locate_phoundation();
        $branch = git_branch($branch, $path);

        return $branch;

    }catch(Exception $e){
        throw new BException('code_phoundation_branch(): Failed', $e);
    }
}



/*
 * Return the current git status for the phoundation project
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see git_list_tags()
 * @version 2.2.0: Added function and documentation
 *
 * @return array Return the available versions for the git project
 */
function code_phoundation_status(){
    try{
        $path   = code_locate_phoundation();
        $status = git_status($path);

        return $status;

    }catch(Exception $e){
        throw new BException('code_phoundation_status(): Failed', $e);
    }
}



/*
 * Return the code line version from the specified version
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @version 2.2.0: Added function and documentation
 * @note Version lines are all tagged versions that share the same MAJOR.MINOR version numbers. E.g. 2.2.0, 2.2.1, 2.2.2, 2.2.3 are all part of the 2.2 code line
 *
 * @param string $version The version to get the version line from
 * @return string the version line for the specified version
 */
function code_get_version_line($version){
    try{
        return str_runtil($version, '.');

    }catch(Exception $e){
        throw new BException('code_get_version_line(): Failed', $e);
    }
}



/*
 * Return the available code lines from the phoundation project
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_locate_phoundation()
 * @see code_get_available_lines()
 * @version 2.2.0: Added function and documentation
 * @note Version lines are all tagged versions that share the same MAJOR.MINOR version numbers. E.g. 2.2.0, 2.2.1, 2.2.2, 2.2.3 are all part of the 2.2 code line
 *
 * @param string $path The root directory of the project where to find the version lines from
 * @return array Return the available version lines for the git project
 */
function code_get_phoundation_lines(){
    try{
        $path = code_locate_phoundation();
        $tags = code_get_available_lines($path);

        return $tags;

    }catch(Exception $e){
        throw new BException('code_get_phoundation_lines(): Failed', $e);
    }
}



/*
 * Return the available versions from the phoundation project
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_locate_phoundation()
 * @see code_get_available_versions()
 * @version 2.2.0: Added function and documentation
 * @note Version lines are all tagged versions that share the same MAJOR.MINOR version numbers. E.g. 2.2.0, 2.2.1, 2.2.2, 2.2.3 are all part of the 2.2 code line
 *
 * @param string $version_lines
 * @return array Return the available versions for the git project
 */
function code_get_phoundation_versions($version_lines = null){
    try{
        $path = code_locate_phoundation();
        $tags = code_get_available_versions($path, $version_lines);

        return $tags;

    }catch(Exception $e){
        throw new BException('code_get_phoundation_versions(): Failed', $e);
    }
}



/*
 * Return the available code lines from the project on the specified path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see git_list_tags()
 * @see code_get_available_versions()
 * @version 2.2.0: Added function and documentation
 * @note Version lines are all tagged versions that share the same MAJOR.MINOR version numbers. E.g. 2.2.0, 2.2.1, 2.2.2, 2.2.3 are all part of the 2.2 code line
 *
 * @param string $path The root directory of the project where to find the version lines from
 * @return array Return the available version lines for the git project
 */
function code_get_available_lines($path = ROOT){
    try{
        $tags   = git_list_tags($path);
        $retval = array();

        foreach($tags as $tag){
            $version = str_from($tag, 'v');

            if(str_is_version($version)){
                $version  = code_get_version_line($version);
                $retval[] = $version;
            }
        }

        return array_unique($retval);

    }catch(Exception $e){
        throw new BException('code_get_available_lines(): Failed', $e);
    }
}



/*
 * Return the available code lines from the project on the specified path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see git_list_tags()
 * @see code_get_available_lines()
 * @version 2.2.0: Added function and documentation
 * @note Version lines are all tagged versions that share the same MAJOR.MINOR version numbers. E.g. 2.2.0, 2.2.1, 2.2.2, 2.2.3 are all part of the 2.2 code line
 *
 * @param string $path The root directory of the project where to find the versions from
 * @return array Return the available version for the git project
 */
function code_get_available_versions($path = ROOT, $version_lines = null){
    try{
        $tags   = git_list_tags($path);
        $retval = array();

        if($version_lines){
            $version_lines = array_force($version_lines);
        }

        foreach($tags as $tag){
            $version = str_from($tag, 'v');

            if(str_is_version($version)){
                $line = code_get_version_line($version);

                if(empty($version_lines) or in_array($line, $version_lines)){
                    $retval[] = $version;
                }
            }
        }

        return array_unique($retval);

    }catch(Exception $e){
        throw new BException('code_get_available_versions(): Failed', $e);
    }
}
?>
