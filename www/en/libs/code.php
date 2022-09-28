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
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @version 2.2.0: Added function and documentation
 *
 * @return void
 */
function code_library_init(){
    try{
        load_libs('git');

    }catch(Exception $e){
        throw new CoreException('code_library_init(): Failed', $e);
    }
}



/*
 * Locate the local Phoundation project and return its path
 *
 * This function will look for the Phoundation system path and return it. The script will first search in the current directory parrallel to ROOT, then one directory higher, then in /var/www/html, then in ~/projects/
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
                         * Check if its really the Phoundation repository by
                         * ensuring the first Phoundation commit hash is
                         * available
                         */
                        try{
                            $result = git_show('290071b81e7bebab9c43aa1fd3a8b691ca1f9695', $path, array('check' => true));
                            $found  = slash(realpath($path));

                        }catch(Exception $e){
                            if($e->getCode() == 128){
                                /*
                                 * The Phoundation initial commit does not
                                 * exist, this is not the Phoundation project!
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
            throw new CoreException(tr('code_locate_phoundation(): Failed to find the Phoundation project in any of the search paths ":paths"', array(':paths' => $paths)), 'warning/not-exists');
        }

        return $found;

    }catch(Exception $e){
        throw new CoreException('code_locate_phoundation(): Failed', $e);
    }
}


/*
 * Locate the local Toolkit project and return its path
 *
 * This function will look for the Toolkit system path and return it. The script will first search in the current directory parrallel to ROOT, then one directory higher, then in /var/www/html, then in ~/projects/
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @version 2.2.0: Added function and documentation
 *
 * @return string the path of the Toolkit project
 */
function code_locate_toolkit(){
    static $found;

    try{
        if(!$found){
            $paths = array(ROOT.'../toolkit.capmega.com/',
                           ROOT.'../../capmega/toolkit.capmega.com/',
                           '/var/www/html/capmega/toolkit.capmega.com/');

            $home = getenv('HOME');

            if($home){
                $paths[] = slash($home).'projects/toolkit.capmega.com/';
            }

            foreach($paths as $path){
                if(file_exists($path)){
                    /*
                     * Found something with the correct name!
                     */
                    if(git_is_repository($path)){
                        /*
                         * Its a git repository too!
                         * Check if its really the Toolkit repository by
                         * ensuring the first Toolkit commit hash is available
                         */
                        try{
                            $result = git_show('290071b81e7bebab9c43aa1fd3a8b691ca1f9695', $path, array('check' => true));
                            $found  = slash(realpath($path));

                        }catch(Exception $e){
                            if($e->getCode() == 128){
                                /*
                                 * The Toolkit initial commit does not
                                 * exist, this is not the Toolkit project!
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
            throw new CoreException(tr('code_locate_toolkit(): Failed to find the Toolkit project in any of the search paths ":paths"', array(':paths' => $paths)), 'warning/not-exists');
        }

        return $found;

    }catch(Exception $e){
        throw new CoreException('code_locate_toolkit(): Failed', $e);
    }
}



/*
 * Download objects and refs from another repository on the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        $path    = code_locate_phoundation();
        $results = git_fetch($path, $params);

        return $results;

    }catch(Exception $e){
        throw new CoreException('code_phoundation_fetch(): Failed', $e);
    }
}



/*
 * Download objects and refs from another repository on the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
function code_phoundation_branch_is_tag($branch = null){
    try{
        $path    = code_locate_phoundation();
        $results = git_branch_is_tag($branch, $path);

        return $results;

    }catch(Exception $e){
        throw new CoreException('code_phoundation_branch_is_tag(): Failed', $e);
    }
}



/*
 * Execute a general "pull" on the current phoundation branch
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
function code_phoundation_pull($remote = 'origin', $branch = null){
    try{
        $path    = code_locate_phoundation();
        $results = git_pull($path, $remote, $branch);

        return $results;

    }catch(Exception $e){
        $data = $e->getData();

        if($data){
            $data  = implode(' ', $data);
            $match = preg_match('/You asked to pull from the remote \'[a-z0-9-_]+\', but did not specify a branch\. Because this is not the default configured remote for your current branch, you must specify a branch on the command line\./', $data);

            if($match){
                throw new CoreException(tr('code_phoundation_pull(): No Phoundation remote branch was specified and the current branch ":branch" has no upstream / default remote branch specified', array(':branch' => git_branch())), 'not-specified');
            }
        }

        throw new CoreException('code_phoundation_pull(): Failed', $e);
    }
}



/*
 * Checkout the specified branch or commit on the phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_locate_phoundation()
 * @see git_branch()
 * @version 2.2.0: Added function and documentation
 *
 * @param string $branch The branch to checkout
 * @return array Return the current branch for the phoundation project
 */
function code_phoundation_checkout($branch){
    try{
        $path   = code_locate_phoundation();
        $branch = git_checkout($branch, $path);

        return $branch;

    }catch(Exception $e){
        throw new CoreException('code_phoundation_checkout(): Failed', $e);
    }
}



/*
 * Set or return the current branch for the phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        throw new CoreException('code_phoundation_branch(): Failed', $e);
    }
}



/*
 * Return the current git status for the phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        throw new CoreException('code_phoundation_status(): Failed', $e);
    }
}



/*
 * Return the code line version from the specified version
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        throw new CoreException('code_get_version_line(): Failed', $e);
    }
}



/*
 * Return the available branches from the phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_locate_phoundation()
 * @see code_get_available_branches()
 * @version 2.2.0: Added function and documentation
 *
 * @return array Return the available branches for the phoundation project
 */
function code_get_phoundation_branch_lines(){
    try{
        $path     = code_locate_phoundation();
        $branches = code_get_branch_lines($path);

        return $branches;

    }catch(Exception $e){
        throw new CoreException('code_get_phoundation_branch_lines(): Failed', $e);
    }
}



/*
 * Return the available code lines from the phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_locate_phoundation()
 * @see code_get_available_lines()
 * @version 2.2.0: Added function and documentation
 * @note Version lines are all tagged versions that share the same MAJOR.MINOR version numbers. E.g. 2.2.0, 2.2.1, 2.2.2, 2.2.3 are all part of the 2.2 code line
 *
 * @return array Return the available version lines for the phoundation project
 */
function code_get_phoundation_lines(){
    try{
        $path = code_locate_phoundation();
        $tags = code_get_available_lines($path);

        return $tags;

    }catch(Exception $e){
        throw new CoreException('code_get_phoundation_lines(): Failed', $e);
    }
}



/*
 * Return the available versions from the phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
 * @return array Return the available versions for the phoundation project
 */
function code_get_phoundation_versions($version_lines = null){
    try{
        $path = code_locate_phoundation();
        $tags = code_get_available_versions($path, $version_lines);

        return $tags;

    }catch(Exception $e){
        throw new CoreException('code_get_phoundation_versions(): Failed', $e);
    }
}



/*
 * Bump the phoundation framework version by incrementing the revision by one
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_get_phoundation_framework_version()
 * @see code_get_available_versions()
 * @see code_get_phoundation_versions()
 * @version 2.2.0: Added function and documentation
 * @note Version lines are all tagged versions that share the same MAJOR.MINOR version numbers. E.g. 2.2.0, 2.2.1, 2.2.2, 2.2.3 are all part of the 2.2 code line
 *
 * @return array Return the framework version for the phoundation project
 */
function code_bump_phoundation_framework_version(){
    try{
        $path     = code_locate_phoundation();
        $version  = code_get_framework_version($path);
        $line     = str_runtil($version, '.');
        $revision = str_rfrom ($version, '.');

        $revision++;
        $version = $line.'.'.$revision;

        code_update_framework_version($version, $path);

        return $version;

    }catch(Exception $e){
        throw new CoreException('code_bump_phoundation_framework_version(): Failed', $e);
    }
}



/*
 * Return the framework version from the phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_locate_phoundation()
 * @see code_get_available_versions()
 * @version 2.2.0: Added function and documentation
 * @note Version lines are all tagged versions that share the same MAJOR.MINOR version numbers. E.g. 2.2.0, 2.2.1, 2.2.2, 2.2.3 are all part of the 2.2 code line
 *
 * @return array Return the framework version for the phoundation project
 */
function code_get_phoundation_framework_version(){
    try{
        $path    = code_locate_phoundation();
        $version = code_get_framework_version($path);

        return $version;

    }catch(Exception $e){
        throw new CoreException('code_get_phoundation_framework_version(): Failed', $e);
    }
}



/*
 * Return the project version from the phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_locate_phoundation()
 * @see code_get_available_versions()
 * @version 2.2.0: Added function and documentation
 * @note Version lines are all tagged versions that share the same MAJOR.MINOR version numbers. E.g. 2.2.0, 2.2.1, 2.2.2, 2.2.3 are all part of the 2.2 code line
 *
 * @return array Return the current project version for the phoundation project
 */
function code_get_phoundation_project_version(){
    try{
        $path    = code_locate_phoundation();
        $version = code_get_project_version($path);

        return $version;

    }catch(Exception $e){
        throw new CoreException('code_get_phoundation_project_version(): Failed', $e);
    }
}



/*
 * Return the available branch version lines from the project on the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see git_list_branches()
 * @version 2.2.0: Added function and documentation
 *
 * @param string $path The root directory of the project where to find the version lines from
 * @return array Return the available branches for the git project
 */
function code_get_branch_lines($path = ROOT){
    try{
        $branches = git_list_branches($path, true);

        foreach($branches as $id => $branch){
            if(!str_is_version($branch.'.0')){
                unset($branches[$id]);
            }
        }

        return $branches;

    }catch(Exception $e){
        throw new CoreException('code_get_branch_lines(): Failed', $e);
    }
}



/*
 * Return the available code lines from the project on the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        throw new CoreException('code_get_available_lines(): Failed', $e);
    }
}



/*
 * Return the available code lines from the project on the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
            $tag     = strtolower($tag);
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
        throw new CoreException('code_get_available_versions(): Failed', $e);
    }
}



/*
 * Return the framework version for the specified phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @version 2.2.0: Added function and documentation
 *
 * @param string $path The root directory of the project where to get the framework version from
 * @return string The current version for the specified Phoundation type  project path
 */
function code_get_framework_version($path = ROOT){
    try{
        $file = slash($path).'libs/system.php';

        if(!file_exists($file)){
            throw new CoreException(tr('code_get_framework_version(): No system library file found for the specified ROOT path ":path"', array(':path' => $path)), 'not-exists');
        }

        $data   = file_get_contents($file);
        $exists = preg_match_all('/define\(\'FRAMEWORKCODEVERSION\',\s+\'(\d+\.\d+\.\d+)\'\);/', $data, $matches);

        if(!$exists){
            throw new CoreException(tr('code_get_framework_version(): Failed to extract project framework version from system library file of Phoundation project in specified path ":path"', array(':path' => $path)), 'failed');
        }

        return $matches[1][0];

    }catch(Exception $e){
        throw new CoreException('code_get_framework_version(): Failed', $e);
    }
}



/*
 * Update the framework version for the specified phoundation project to the specified version
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @version 2.2.0: Added function and documentation
 *
 * @param string $path The root directory of the project where to get the framework version from
 * @return string The current version for the specified Phoundation type  project path
 */
function code_update_framework_version($version, $path = ROOT){
    try{
        $file = slash($path).'libs/system.php';

        if(!file_exists($file)){
            throw new CoreException(tr('code_get_framework_version(): No system library file found for the specified ROOT path ":path"', array(':path' => $path)), 'not-exists');
        }

        $data = file_get_contents($file);
        $data = preg_replace('/define\(\'FRAMEWORKCODEVERSION\',\s+\'(\d+\.\d+\.\d+)\'\);/', "define('FRAMEWORKCODEVERSION', '".$version."');", $data);

        file_put_contents($file, $data);

    }catch(Exception $e){
        throw new CoreException('code_update_framework_version(): Failed', $e);
    }
}



/*
 * Return the project version for the specified phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @version 2.2.0: Added function and documentation
 *
 * @param string $path The root directory of the project where to get the project version from
 * @return string The current version for the specified Phoundation type  project path
 */
function code_get_project_version($path = ROOT){
    try{
        $file = slash($path).'config/project.php';

        if(!file_exists($file)){
            throw new CoreException(tr('code_get_project_version(): No project configuration file found for the specified ROOT path ":path"', array(':path' => $path)), 'not-exists');
        }

        $data   = file_get_contents($file);
        $exists = preg_match_all('/define\(\'PROJECTCODEVERSION\',\s+\'(\d+\.\d+\.\d+)\'\);/', $data, $matches);

        if(!$exists){
            throw new CoreException(tr('code_get_project_version(): Failed to extract project code version from project file of Phoundation project in specified path ":path"', array(':path' => $path)), 'failed');
        }

        return $matches[1][0];

    }catch(Exception $e){
        throw new CoreException('code_get_project_version(): Failed', $e);
    }
}



/*
 * Check if the specified file exists in the Toolkit project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_diff_phoundation()
 * @see code_diff_toolkit()
 * @version 2.2.0: Added function and documentation
 *
 * @param string $file The file to be checked
 * @return boolean True if the specified file exists in the Toolkit project, false if not
 */
function code_file_exists_in_phoundation($file){
    try{
        $path = code_locate_phoundation();
        return file_exists($path.$file);

    }catch(Exception $e){
        throw new CoreException('code_file_exists_in_phoundation(): Failed', $e);
    }
}



/*
 * Check if the specified file exists in the Toolkit project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_diff_phoundation()
 * @see code_diff_toolkit()
 * @version 2.2.0: Added function and documentation
 *
 * @param string $file The file to be checked
 * @return boolean True if the specified file exists in the Toolkit project, false if not
 */
function code_file_exists_in_toolkit($file){
    try{
        $path = code_locate_toolkit();
        return file_exists($path.$file);


    }catch(Exception $e){
        throw new CoreException('code_file_exists_in_toolkit(): Failed', $e);
    }
}



/*
 * Perform a diff between the two specified files
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_diff_phoundation()
 * @see code_diff_toolkit()
 * @version 2.2.0: Added function and documentation
 *
 * @param string $file The first file to be compared
 * @param string $file2 The second file to be compared
 * @return string The difference between the two files
 */
function code_diff($file, $file2){
    try{
        return safe_exec(array('ok_exitcodes' => 1,
                               'commands'     => array('diff', array($file, $file2))));

    }catch(Exception $e){
        throw new CoreException('code_diff(): Failed', $e);
    }
}



/*
 * Perform a diff between the specified file in this project and the same file in the Phoundation project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_diff()
 * @see code_diff_toolkit()
 * @version 2.2.0: Added function and documentation
 *
 * @param string $file The first file to be compared
 * @param string $file2 The second file to be compared
 * @return string The difference between the two files
 */
function code_diff_phoundation($file){
    try{
        $path = code_locate_phoundation();
        return code_diff(ROOT.$file, $path.$file);

    }catch(Exception $e){
        throw new CoreException('code_diff_phoundation(): Failed', $e);
    }
}



/*
 * Perform a diff between the specified file in this project and the same file in the Toolkit project
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see code_diff()
 * @see code_diff_phoundation()
 * @version 2.2.0: Added function and documentation
 *
 * @param string $file The first file to be compared
 * @param string $file2 The second file to be compared
 * @return string The difference between the two files
 */
function code_diff_toolkit($file){
    try{
        $path = code_locate_toolkit();
        return code_diff(ROOT.$file, $path.$file);

    }catch(Exception $e){
        throw new CoreException('code_diff_toolkit(): Failed', $e);
    }
}



/*
 * Get git diff for the specified file and try to apply it on the Phoundation or Toolkit projects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package code
 * @see git_diff()
 * @see git_apply()
 * @version 2.2.0: Added function and documentation
 *
 * @param params $params The patch parameters
 * @param string $params[file] The first file to be compared
 * @param string $params[path]
 * @param string $params[method]
 * @param array $params[replaces]
 * @param boolean $params[clean] If set to true, delete the patch file
 * @param null array $params[restrictions] If set, restrict function access to the specified paths only
 * @return string The patch result, either "patched", "created" or "deleted"
 */
function code_patch($params){
    try{
        array_ensure($params, 'file,source_path,target_path,replaces,clean,restrictions');
        array_default($params, 'method'     , 'apply');
        array_default($params, 'source_path', ROOT);
        array_default($params, 'clean'      , true);

        file_restrict($params);

        if(!$params['target_path']){
            throw new CoreException(tr('code_patch(): No target path specified'), 'empty');
        }

        if(!file_exists($params['source_path'])){
            throw new CoreException(tr('code_patch(): Specified source path ":source" does not exist', array(':source' => $params['source_path'])), 'not-exist');
        }

        if(!file_exists($params['target_path'])){
            throw new CoreException(tr('code_patch(): Specified target path ":target" does not exist', array(':target' => $params['target_path'])), 'not-exist');
        }

        $params['source_path'] = slash($params['source_path']);
        $params['target_path'] = slash($params['target_path']);

        switch($params['method']){
            case 'diff':
                log_console(tr('Showing diff patch for file ":file"', array(':file' => $params['file'])), 'white');
                echo git_diff($params['file'], !NOCOLOR);
                break;

            case 'create':
                // FALLTHROUGH
            case 'apply':
                // FALLTHROUGH
            case 'patch':
                log_console(tr('Trying to patch ":file"', array(':file' => $params['file'])), 'VERBOSE/cyan');

                if(file_exists($params['target_path'].$params['file'])){
                    /*
                     * The target file exists. Send the changed by patch
                     */
                    $patch      = git_diff($params['source_path'].$params['file']);
                    $patch_file = slash($params['target_path']).sha1($params['file']).'.patch';

                    if(empty($patch)){
                        throw new CoreException(tr('code_patch(): The function git_diff() returned empty patch data for file ":file"', array(':file' => $params['file'])), 'empty');
                    }

                    if($params['replaces']){
                        /*
                         * Perform a search / replace on the patch data
                         */
                        foreach($params['replaces'] as $search => $replace){
                            $patch = str_replace($search, $replace, $patch);
                        }
                    }

                    file_put_contents($patch_file, implode("\n", $patch)."\n");

                    if($params['method'] == 'create'){
                        /*
                         * Don't actually apply the patch
                         */

                    }else{
                        git_apply($patch_file);

                        if($params['clean']){
                            file_delete($patch_file, $params['restrictions']);
                        }
                    }

                    return 'patched';
                }

                /*
                 * The target file does not exist, so just copy it
                 */
                copy($params['source_path'].$params['file'], $params['target_path'].str_replace('admin/', '', $params['file']));
                return 'created';

            default:
                throw new CoreException(tr('code_patch(): Unknown method ":method" specified', array(':method' => $params['method'])), 'unknown');
        }

    }catch(Exception $e){
        throw new CoreException(tr('code_patch(): Failed for file ":file"', array(':file' => $params['file'])), $e, array('patch_file' => isset_get($patch_file)));
    }
}
?>
