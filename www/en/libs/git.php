<?php
/*
 * GIT library
 *
 * This library contains functions to manage GIT
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package git
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
 * @package git
 * @version 2.0.5: Added function and documentation
 *
 * @return void
 */
function git_library_init() {
    try {
        load_libs('cli');
        load_config('git');

    }catch(Exception $e) {
        throw new CoreException('git_library_init(): Failed', $e);
    }
}



/*
 * Returns true if the specified path is part of a git repository, false if not
 *
 * A path is part of a git repository if one if its parent directories contains a .git directory
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 * @version 2.0.6: Added function and documentation
 *
 * @param string $path The path to be checked
 * @return boolean True if the specified path is part of a git repository, false if not
 */
function git_is_repository($path = ROOT) {
    try {
        return (boolean) git_check_path($path, false);

    }catch(Exception $e) {
        throw new CoreException('git_is_repository(): Failed', $e);
    }
}



/*
 * Returns true if the git command is available, false if not
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 * @version 2.0.6: Added function and documentation
 *
 * @return boolean True if the git command is available, false if not
 */
function git_is_available() {
    try {
        return (boolean) file_which('git');

    }catch(Exception $e) {
        throw new CoreException('git_is_available(): Failed', $e);
    }
}



/*
 * Ensure the path is specified, exists, and contains a git repository
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @param boolean $exception If set true, this function will throw an exception if the specified path is not a git repository
 * @return The specified path, checked
 */
function git_check_path($path, $exception = true) {
    static $paths;

    try {
        if (isset($paths[$path])) {
            return $paths[$path];
        }

        if (!$path) {
            $path = ROOT;
        }

        if (!file_exists($path)) {
            if ($exception) {
                throw new CoreException(tr('git_check_path(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
            }

            return false;
        }

        if (!file_scan($path, '.git')) {
            if ($exception) {
                throw new CoreException(tr('git_check_path(): Specified path ":path" is not a git repository', array(':path' => $path)), 'git');
            }

            return false;
        }

        $paths[$path] = $path;
        return $path;

    }catch(Exception $e) {
        throw new CoreException('git_check_path(): Failed', $e);
    }
}



/*
 * Execute the specified git method with the specified arguments
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string
 * @param string $patch_file
 * @param null array $arguments
 * @return
 */
function git_exec($path, $arguments, $check_path = true) {
    try {
        if ($check_path) {
            $path = git_check_path($path);
        }

        git_wait_no_process($path);

        if (!$arguments) {
            $arguments = array();
        }

        $arguments['timeout'] = 30;

        $results = safe_exec(array('commands' => array('cd' , array($path),
                                                       'git', $arguments)));

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_exec(): Failed', $e);
    }
}



/*
 * Checks if another git process is running on the specified path. If so, wait for that process to finish, then continue
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 * @version 2.5.272: Added function and documentation
 *
 * @return void
 */
function git_wait_no_process($path) {
    global $_CONFIG;

    try {
        $pids = cli_pgrep('git');

        foreach($pids as $pid) {
            $process = cli_pidgrep($pid);
            $process = Strings::until($process, ' ');

            if ($process === 'git') {
                /*
                 * We found a git process, but is it on the specified path?
                 */
                $git_path = cli_get_cwd($pid, true);
                $exists   = (str_contains($git_path, $path) or str_contains($path, $git_path));

                if (!isset($retry)) {
                    $retry = $_CONFIG['git']['retries'];
                }

                while ($exists) {
                    /*
                     * Git is already being used on our target path! Wait up to
                     * the one second and retry the configured amount of times
                     */
                    if ($retry <= 0) {
                        throw new CoreException(tr('git_wait_no_process(): The target path ":path" is occupied by the process ":pid", and the waiting period timed out after ":tries" tries', array(':path' => $path, ':pid' => $pid, ':tries' => $_CONFIG['git']['retries'])), 'busy');
                    }

                    log_console(tr('Found git process already working on target path ":path", retrying ":tries" times in 1 second.', array(':path' => $path, ':tries' => $retry)), 'yellow');

                    if (cli_pidgrep($pid)) {
                        sleep(1);
                        $retry--;
                        continue;
                    }

                    /*
                     * The PID that was using the path is gone! Doesn't mean
                     * though that they are all gone, so retry and be sure the
                     * are no processes left
                     */
                    return git_wait_no_process($path);
                }
            }
        }

    }catch(Exception $e) {
        throw new CoreException('git_wait_no_process(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $file
 * @param string $patch_file
 * @return
 */
function git_am($file, $patch_file) {
    try {
        $path    = dirname($patch_file);
        $results = git_exec($path, array('am', basename($patch_file), '<'));

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_am(): Failed', $e);
    }
}



/*
 * Apply a git patch file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $file
 * @return
 */
function git_apply($file) {
    try {
        $path    = dirname($file);
        $results = git_exec($path, array('apply', basename($file)));

        return $results;

    }catch(Exception $e) {
        $data = Arrays::force($e->getData());
        $data = array_pop($data);

        if (strstr($data, 'patch does not apply')) {
            throw new CoreException(tr('git_apply(): patch ":file" does not apply', array(':file' => $file)), 'failed');
        }

        throw new CoreException('git_apply(): Failed', $e);
    }
}



/*
 * Get or set the current GIT branch
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $branch
 * @param string $path
 * @return
 */
function git_branch($branch = null, $path = ROOT) {
    try {
        if ($branch) {
            /*
             * Set the branch
             */
            git_exec($path, array('branch', $branch));
        }

        /*
         * Get and return the branch
         */
        $results = git_exec($path, array('branch'));

        foreach($results as $branch) {
            if (substr(trim($branch), 0, 1) == '*') {
                $branch = trim(substr(trim($branch), 1));
                $branch = strtolower(Strings::cut(($branch, '(', ')'));
                $branch = trim(Strings::from($branch, 'head detached at'));

                return $branch;
            }
        }

        throw new CoreException(tr('git_branch(): Could not find current branch for ":path"', array(':path' => $path)), 'not-exists');

    }catch(Exception $e) {
        throw new CoreException('git_branch(): Failed', $e);
    }
}



/*
 * Get and return the available GIT branches for the specified git repository path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @param boolean $all If set to true, list both remote-tracking branches and local branches.
 * @return array All available branches on the specified git project path
 */
function git_list_branches($path = ROOT, $all = false) {
    try {
        /*
         * Get and return the branch
         */
        $retval  = array();
        $results = git_exec($path, array('branch', '-a', '-q'));

        foreach($results as $branch) {
            $branch = Strings::until($branch, '->');
            $branch = trim($branch);
            $branch = Strings::fromReverse($branch, '/');

            $retval[] = $branch;
        }

        $retval = array_unique($retval);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('git_list_branches(): Failed', $e);
    }
}



/*
 * Checkout the specified file, resetting its changes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @param string $branch
 * @param boolean $create
 * @return
 */
function git_checkout($branch = null, $path = ROOT, $create = false) {
    try {
        if ($branch) {
            $results = git_exec($path, array('checkout', ($create ? ' -B ' : ''), $branch));

        } else {
            if (is_dir($path)) {
                $results = git_exec($path, array('checkout', '--', $path));

            } else {
                $file    = basename($path);
                $path    = dirname($path);
                $results = git_exec($path, array('checkout', '--', $file));
            }
        }

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_checkout(): Failed', $e);
    }
}



/*
 * Clean the specified git repository
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @param boolean $directories
 * @param boolean $force
 * @return
 */
function git_clean($path = ROOT, $directories = false, $force = false) {
    try {
        $results = git_exec($path, array('clean', ($directories ? ' -d' : ''), ($force ? ' -f' : '')));
        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_clean(): Failed', $e);
    }
}



/*
 * Clone the specified git repository to the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $repository The git repository URL to be cloned
 * @param string $path The path where the git repository must be cloned
 * @param boolean $clean If set to true, this function will remove the .git repository directory from the cloned project, leaving only the working tree
 * @return string The path of the cloned repository
 */
function git_clone($repository, $path, $clean = false) {
    try {
        /*
         * Clone the repository
         */
        $results = git_exec($path, array('clone', $repository), false);

        if ($clean) {
            /*
             * Delete the .git repository file, leaving on the working tree
             */
            file_delete(Strings::slash($path).$repository.'/.git', $path);
        }

        return Strings::slash($path).$repository;

    }catch(Exception $e) {
        throw new CoreException('git_clone(): Failed', $e);
    }
}



/*
 * Make a patch for the specified file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $file
 * @param string $color
 * @return
 */
function git_diff($file, $color = false) {
    try {
        $path    = dirname($file);
        $results = git_exec($path, array('diff', ($color ? '' : '--no-color '), '--', basename($file)));

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_diff(): Failed', $e);
    }
}



/*
 * Get the changes for the specified commit
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $commit The commit to get the changes and information for
 * @param string $path
 * @return
 */
function git_show($commit, $path = ROOT, $params = null) {
    try {
        Arrays::ensure($params, 'check');

        $arguments = array('show');

        if ($params['check']) {
            $arguments[] = '--check';
        }

        $arguments[] = $commit;
        $arguments[] = '--';
        $results     = git_exec($path, $arguments);

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_show(): Failed', $e);
    }
}



/*
 * Download objects and refs from another repository on the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param params $params
 * @return
 */
function git_fetch($path = ROOT, $params = null) {
    try {
        Arrays::ensure($params, 'tags,all');

        $arguments = array('fetch');

        if ($params['all']) {
            $arguments[] = '--all';
        }

        if ($params['tags']) {
            $arguments[] = '--tags';
        }

        /*
         * Execute a git fetch
         */
        $results = git_exec($path, $arguments);

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_fetch(): Failed', $e);
    }
}



/*
 * Make a patch for the specified file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $file
 * @return
 */
function git_format_patch($file) {
    try {
under_construction();
        $path    = dirname($file);
        $results = git_exec($path, array('format-patch', $file));

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_format_patch(): Failed', $e);
    }
}



/*
 * Return the current branch for the specified git repository
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $branch
 * @return
 */
function git_branch_is_tag($branch = null, $path = ROOT) {
    try {
        $tags     = git_list_tags($path);
        $branches = git_list_branches($path);

        if (!$branch) {
            /*
             * Get the current branch
             */
            $branch = git_branch(null, $path);
        }

        /*
         * Does the specified branch exist as a tag?
         */
        if (in_array($branch, $tags)) {
            return true;
        }

        /*
         * Does the specified branch exist as a branch?
         */
        if (in_array($branch, $branches)) {
            return false;
        }

        throw new CoreException(tr('git_branch_is_tag(): Specified branch or tag ":branch" does not exist', array(':branch' => $branch)), 'not-exists');

    }catch(Exception $e) {
        throw new CoreException('git_branch_is_tag(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @param string $remote
 * @param string $branch
 * @return
 */
function git_pull($path = ROOT, $remote, $branch) {
    try {
        $results = git_exec($path, array('pull', $remote, $branch));

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_pull(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @param null string $remote
 * @param null string $branch
 * @param boolean $tags
 * @return
 */
function git_push($path = ROOT, $remote = null, $branch = null, $tags = true) {
    try {
        if ($branch and !$remote) {
            throw new CoreException(tr('git_push(): Branch ":branch" was specified without remote', array(':branch' => $branch)), 'invalid');
        }

        $results = git_exec($path, array('push', $remote, $branch));

        if ($tags) {
            $tags    = git_exec($path, array('push', '--tags', $remote, $branch));
            $results = array_merge($results, $tags);
        }

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_push(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $file
 * @param null string $commit
 * @return
 */
function git_reset($commit = 'HEAD', $path = ROOT, $params = null) {
    try {
        $file = $path;

        if (!is_dir($path)) {
            $path = dirname($file);
        }

        Arrays::ensure($params, 'hard');
        $options = '';

        if ($params['hard']) {
            $options .= ' --hard ';
        }

        $results = git_exec($path, array('reset', ($commit ? $commit.' ' : ''), $file));

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_reset(): Failed', $e);
    }
}



/*
 * Return an associative array with as key => value $file => $status
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $params
 * @param null array $filters
 * @return
 */
function git_status($path = ROOT, $filters = null) {
    try {
        /*
         * Check if we dont have any changes that should be committed first
         */
        $retval  = array();
        $results = git_exec($path, array('status', '--porcelain'));

        foreach($results as $line) {
            if (!$line) continue;

            $status = substr($line, 0, 2);

            if ($filters) {
                /*
                 * Only allow files that have status in the filter
                 */
                $skip = true;

                foreach($filters as $filter) {
                    if ($status == $filter) {
                        $skip = false;
                    }
                }

                if ($skip) continue;
            }

            switch($status) {
                case 'D ':
                    $status = 'deleted';
                    break;

                case ' T':
                    $status = 'typechange';
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

                case 'MM':
                    $status = 'modified and modified on index';
                    break;

                case '??':
                    $status = 'not tracked';
                    break;

                case 'UU':
                    $status = 'Both modified';
                    break;

                default:
                    throw new CoreException(tr('git_status(): Unknown git status ":status" encountered for file ":file"', array(':status' => $status, ':file' => substr($line, 3))), 'unknown');
            }

            $retval[substr($line, 3)] = $status;
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('git_status(): Failed', $e);
    }
}



/*
 * Return a list of available tags for the specified repository
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return array The available tags for the specified git repository
 */
function git_list_tags($path = ROOT) {
    try {
        /*
         * Check if we dont have any changes that should be committed first
         */
        $results = git_exec($path, array('tag', '--list'));

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_list_tags(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return
 */
function git_stash($path = ROOT) {
    try {
        $results_add   = git_exec($path, array('add', '.'));
        $results_stash = git_exec($path, array('stash'));
        $results       = array_merge($results_add, $results_stash);

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_stash(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return
 */
function git_stash_pop($path = ROOT) {
    try {
        $results_pop   = git_exec($path, array('stash', 'pop'));
        $results_reset = git_exec($path, array('reset', 'HEAD'));
        $results       = array_merge($results_pop, $results_reset);

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_stash_pop(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return
 */
function git_add($path = ROOT) {
    try {
        if (is_dir($path)) {
            $results = git_exec($path, array('add', $path));

        } else {
            $results = git_exec(dirname($path), array('add', $path));
        }

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_add(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package git
 *
 * @param string $path
 * @return
 */
function git_commit($message, $path = ROOT) {
    try {
        $results = git_exec($path, array('commit', '-m', $message));

        return $results;

    }catch(Exception $e) {
        throw new CoreException('git_commit(): Failed', $e);
    }
}
?>
