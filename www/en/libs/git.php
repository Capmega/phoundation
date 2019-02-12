<?php
/*
 * GIT library
 *
 * This library contains functions to manage GIT
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package git
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
 * @package git
 * @version 2.0.5: Added function and documentation
 *
 * @return void
 */
function git_library_init(){
    try{
        load_libs('cli');

    }catch(Exception $e){
        throw new BException('git_library_init(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $file
 * @param string $patch_file
 * @return
 */
function git_am($file, $patch_file){
    try{
        $path = dirname($file);
        git_check_path($path);

        $result = safe_exec('cd '.$path.'; git apply '.basename($file),' <');

        return $result;

    }catch(Exception $e){
        throw new BException('git_am(): Failed', $e);
    }
}



/*
 * Apply a git patch file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $file
 * @return
 */
function git_apply($file){
    try{
        $path = dirname($file);
        git_check_path($path);

        $result = safe_exec('cd '.$path.'; git apply '.basename($file));

        return $result;

    }catch(Exception $e){
        $data = array_force($e->getData());
        $data = array_pop($data);

        if(strstr($data, 'patch does not apply')){
            throw new BException(tr('git_apply(): Failed to apply patch ":file"', array(':file' => $file)), 'failed');
        }

        throw new BException('git_apply(): Failed', $e);
    }
}



/*
 * Get or set the current GIT branch
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $branch
 * @param string $path
 * @return
 */
function git_branch($branch = null, $path = ROOT){
    try{
        git_check_path($path);

        if($branch){
            /*
             * Set the branch
             */
            safe_exec('cd '.$path.'; git branch '.$branch);
        }

        /*
         * Get and return the branch
         */
        $branches = safe_exec('cd '.$path.'; git branch');

        foreach($branches as $branch){
            if(substr(trim($branch), 0, 1) == '*'){
                return trim(substr(trim($branch), 1));
            }
        }

        throw new BException(tr('git_branch(): Could not find current branch for ":path"', array(':path' => $path)), 'not-exists');

    }catch(Exception $e){
        throw new BException('git_branch(): Failed', $e);
    }
}



/*
 * Get and return the available GIT branches for the specified git repository path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @param boolean $all If set to true, list both remote-tracking branches and local branches.
 * @return array All available branches on the specified git project path
 */
function git_list_branches($path = ROOT, $all = false){
    try{
        git_check_path($path);

        /*
         * Get and return the branch
         */
        $branches = safe_exec('cd '.$path.'; git branch -a -q');

        foreach($branches as $branch){
            $branch = str_until($branch, '->');
            $branch = trim($branch);
            $branch = str_rfrom($branch, '/');

            $retval [] = $branch;
        }

        $retval = array_unique($retval);
        return $retval;

    }catch(Exception $e){
        throw new BException('git_list_branches(): Failed', $e);
    }
}



/*
 * Ensure the path is specified and exists
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return
 */
function git_check_path(&$path){
    static $paths;

    try{
        if(isset($paths[$path])){
            return $paths[$path];
        }

        if(!$path){
            $path = ROOT;
        }

        if(!file_exists($path)){
            throw new BException(tr('git_check_path(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exist');
        }

        if(!file_scan($path, '.git')){
            throw new BException(tr('git_check_path(): Specified path ":path" is not a git repository', array(':path' => $path)), 'git');
        }

        $paths[$path] = true;
        return true;

    }catch(Exception $e){
        throw new BException('git_check_path(): Failed', $e);
    }
}



/*
 * Checkout the specified file, resetting its changes
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @param string $branch
 * @param boolean $create
 * @return
 */
function git_checkout($branch = null, $path = ROOT, $create = false){
    try{
        if($branch){
            safe_exec('cd '.$path.'; git checkout '.($create ? ' -B ' : '').$branch);

        }else{
            if(is_dir($path)){
                git_check_path($path);
                safe_exec('cd '.$path.'; git checkout -- '.$path);

            }else{
                $file = basename($path);
                $path = dirname($path);

                git_check_path($path);
                safe_exec('cd '.$path.'; git checkout -- '.$file);
            }
        }

    }catch(Exception $e){
        throw new BException('git_checkout(): Failed', $e);
    }
}



/*
 * Clean the specified git repository
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @param boolean $directories
 * @param boolean $force
 * @return
 */
function git_clean($path = ROOT, $directories = false, $force = false){
    try{
        $retval = safe_exec('cd '.$path.'; git clean'.($directories ? ' -d' : '').($force ? ' -f' : ''));

    }catch(Exception $e){
        throw new BException('git_clean(): Failed', $e);
    }
}



/*
 * Clone the specified git repository to the specified path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $repository The git repository URL to be cloned
 * @param string $path The path where the git repository must be cloned
 * @param boolean $clean If set to true, this function will remove the .git repository directory from the cloned project, leaving only the working tree
 * @return string The path of the cloned repository
 */
function git_clone($repository, $path, $clean = false){
    try{
        /*
         * Clone the repository
         */
        safe_exec('cd '.$path.'; git clone '.$repository);

        if($clean){
            /*
             * Delete the .git repository file, leaving on the working tree
             */
            file_delete(slash($path).$repository.'/.git');
        }

        return slash($path).$repository;

    }catch(Exception $e){
        throw new BException('git_clone(): Failed', $e);
    }
}



/*
 * Make a patch for the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $file
 * @param string $color
 * @return
 */
function git_diff($file, $color = false){
    try{
        $path = dirname($file);
        git_check_path($path);

        $result = safe_exec('cd '.$path.'; git diff '.($color ? '' : '--no-color ').' -- '.basename($file));

        return $result;

    }catch(Exception $e){
        throw new BException('git_diff(): Failed', $e);
    }
}



/*
 * Get the changes for the specified commit
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $commit The commit to get the changes and information for
 * @param string $path
 * @return
 */
function git_show($commit, $path = ROOT, $params = null){
    try{
        array_ensure($params, 'check');
        $options = '';

        if($params['check']){
            $options .= ' --check ';
        }

        git_check_path($path);
        $result = safe_exec('cd '.$path.'; git show '.$options.$commit.' --');
        return $result;

    }catch(Exception $e){
        throw new BException('git_show(): Failed', $e);
    }
}



/*
 * Download objects and refs from another repository on the specified path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param params $params
 * @return
 */
function git_fetch($path = ROOT, $params = null){
    try{
        array_params($params, 'tags,all');
        git_check_path($path);

        $options = '';

        if($params['all']){
            $options .= ' --all ';
        }

        if($params['tags']){
            $options .= ' --tags ';
        }

        /*
         * Execute a git fetch
         */
        return safe_exec('cd '.$path.'; git fetch '.$options);

    }catch(Exception $e){
        throw new BException('git_fetch(): Failed', $e);
    }
}



/*
 * Make a patch for the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $file
 * @return
 */
function git_format_patch($file){
    try{
        $path = dirname($file);
        git_check_path($path);
under_construction();
        $result = safe_exec('cd '.$path.'; git format-patch ');

        return $result;

    }catch(Exception $e){
        throw new BException('git_format_patch(): Failed', $e);
    }
}



/*
 * Return the current branch for the specified git repository
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $branch
 * @return
 */
function git_get_branch($branch = null){
    try{
        git_check_path($path);

        $branches = safe_exec('cd '.$path.'; git branch --no-color');

        foreach($branches as $line){
            $current = trim(substr($line, 0, 2));

            if($current){
                return trim(substr($line, 2));
            }
        }

        return null;

    }catch(Exception $e){
        throw new BException('git_get_branch(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @param string $remote
 * @param string $branch
 * @return
 */
function git_pull($path, $remote, $branch){
    try{
        git_check_path($path);
        safe_exec('cd '.$path.'; git pull '.$remote.' '.$branch);

    }catch(Exception $e){
        throw new BException('git_pull(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $file
 * @param null string $commit
 * @return
 */
function git_reset($commit = 'HEAD', $path = ROOT, $params = null){
    try{
        $file = $path;

        if(!is_dir($path)){
            $path = dirname($file);
        }

        git_check_path($path);

        array_ensure($params, 'hard');
        $options = '';

        if($params['hard']){
            $options .= ' --hard ';
        }

        $retval = safe_exec('cd '.$path.'; git reset '.($commit ? $commit.' ' : '').$file);

    }catch(Exception $e){
        throw new BException('git_reset(): Failed', $e);
    }
}



/*
 * Return an associative array with as key => value $file => $status
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $params
 * @param null array $filters
 * @return
 */
function git_status($path = ROOT, $filters = null){
    try{
        git_check_path($path);

        /*
         * Check if we dont have any changes that should be committed first
         */
        $retval  = array();
        $results = safe_exec('cd '.$path.'; git status --porcelain');

        foreach($results as $line){
            if(!$line) continue;

            $status = substr($line, 0, 2);

            if($filters){
                /*
                 * Only allow files that have status in the filter
                 */
                $skip = true;

                foreach($filters as $filter){
                    if($status == $filter){
                        $skip = false;
                    }
                }

                if($skip) continue;
            }

            switch($status){
                case 'D ':
                    $status = 'deleted';
                    break;

                case ' D':
                    $status = 'deleted';
                    break;

                case 'AD':
                    $status = 'added to index but deleted';
                    break;

                case 'A ':
                    $status = 'new file added to index';
                    break;

                case 'AM':
                    $status = 'new file';
                    break;

                case ' M':
                    $status = 'modified';
                    break;

                case 'RM':
                    $status = 'renamed modified';
                    break;

                case 'M ':
                    $status = 'modified indexed';
                    break;

                case '??':
                    $status = 'not tracked';
                    break;

                default:
                    throw new BException(tr('git_status(): Unknown git status ":status" encountered for file ":file"', array(':status' => $status, ':file' => substr($line, 3))), 'unknown');
            }

            $retval[substr($line, 3)] = $status;
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('git_status(): Failed', $e);
    }
}



/*
 * Return a list of available tags for the specified repository
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return array The available tags for the specified git repository
 */
function git_list_tags($path = ROOT){
    try{
        git_check_path($path);

        /*
         * Check if we dont have any changes that should be committed first
         */
        $results = safe_exec('cd '.$path.'; git tag --list');
        return $results;

    }catch(Exception $e){
        throw new BException('git_list_tags(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return
 */
function git_stash($path = ROOT){
    try{
        git_check_path($path);

        $result = safe_exec('cd '.$path.'; git add .; git stash');

        return $result;

    }catch(Exception $e){
        throw new BException('git_stash(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return
 */
function git_stash_pop($path = ROOT){
    try{
        git_check_path($path);

        $result = safe_exec('cd '.$path.'; git stash pop');

        return $result;

    }catch(Exception $e){
        throw new BException('git_stash_pop(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return
 */
function git_add($path = ROOT){
    try{
        git_check_path($path);

        if(is_dir($path)){
            $result = safe_exec('cd "'.$path.'"; git add "'.$path.'"');

        }else{
            $result = safe_exec('cd "'.dirname($path).'"; git add "'.$path.'"');
        }

        return $result;

    }catch(Exception $e){
        throw new BException('git_add(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return
 */
function git_commit($message, $path = ROOT){
    try{
        git_check_path($path);

        $result = safe_exec('cd '.$path.'; git commit -m "'.$message.'"');

        return $result;

    }catch(Exception $e){
        throw new BException('git_commit(): Failed', $e);
    }
}



/*
 * Returns true if the specified path is part of a git repository, false if not
 *
 * A path is part of a git repository if one if its parent directories contains a .git directory
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 * @version 2.0.6: Added function and documentation
 *
 * @param string $path The path to be checked
 * @return boolean True if the specified path is part of a git repository, false if not
 */
function git_is_repository($path = ROOT){
    try{
        git_check_path($path);

        while($path){
            if(file_exists(slash($path).'.git')){
                return true;
            }

            $path = str_runtil($path, '/');
        }

        return false;

    }catch(Exception $e){
        throw new BException('git_commit(): Failed', $e);
    }
}



/*
 * Returns true if the git command is available, false if not
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 * @version 2.0.6: Added function and documentation
 *
 * @return boolean True if the git command is available, false if not
 */
function git_is_available(){
    try{
        return (boolean) cli_which('git');

    }catch(Exception $e){
        throw new BException('git_is_available(): Failed', $e);
    }
}
?>
