<?php
/*
 * Rsync library
 *
 * This library is an rsync frontend and contains functions to
 * sync directories from local server with remote server
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */


/*
 * Front end for the command line rsync command. Performs rsync with the specified parameters
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package rsync
 *
 * @param array $params
 * @params string source
 * @params string target
 * @params boolean passthru passes output of the rsync command straight to the console or client. Defaults to true on CLI mode, and false on HTTP mode
 * @params boolean archive archive mode (-a or --archive in rsync)
 * @params boolean checksum skip based on checksum, not mod-time & size (--checksum in rsync)
 * @params boolean compression compress file data during the transfer (-z or --compress in rsync)
 * @params boolean delete
 * @params boolean force
 * @params boolean group
 * @params boolean links copy symbolic links as symbolic links and not their targets
 * @params boolean owner
 * @params boolean permissions
 * @params boolean recursive
 * @params boolean super
 * @params boolean time
 * @params string ssh_string The command used to execute SSH. Defaults to "ssh", but can be used as (for example) "ssh -e 6375" to have SSH connect to a port different than the default 22
 * @params string remote_rsync How to run rsync on the remote server (if aplicable). Example: 'sudo rsync' to run rsync on the remote server as root
 * @return void
 *
 * @example
 * rsync(array('source' => ROOT
 *             'target' => server:/))
 * rsync -acz --progress -p --delete -e "ssh -p '.$_CONFIG['deploy']['target_port'].'" '.ROOT.' '.$_CONFIG['deploy']['target_user'].'@'.$_CONFIG['deploy']['target_server'].':'.$_CONFIG['deploy']['target_dir'].' '.$exclude
 */
function rsync($params) {
    load_libs('servers');

    try{
        /*
         * The optional parameters
         */
        array_ensure($params);
        array_default($params, 'append'             , false);
        array_default($params, 'append_verify'      , false);
        array_default($params, 'archive'            , true);
        array_default($params, 'background'         , false);
        array_default($params, 'checksum'           , true);
        array_default($params, 'compression'        , true);
        array_default($params, 'delete'             , false);
        array_default($params, 'exclude'            , null);
        array_default($params, 'exitcodes'          , null);
        array_default($params, 'force'              , true);
        array_default($params, 'function'           , (PLATFORM_CLI ? 'passthru' : null));
        array_default($params, 'group'              , true);
        array_default($params, 'inplace'            , true);
        array_default($params, 'links'              , true);
        array_default($params, 'monitor_pid'        , false);
        array_default($params, 'monitor_task'       , false);
        array_default($params, 'owner'              , true);
        array_default($params, 'permissions'        , true);
        array_default($params, 'port'               , null);
        array_default($params, 'progress'           , true);
        array_default($params, 'recursive'          , true);
        array_default($params, 'remote_rsync'       , false);
        array_default($params, 'remove_source_files', true);
        array_default($params, 'ssh_options'        , null);
        array_default($params, 'super'              , false);
        array_default($params, 'time'               , true);
        array_default($params, 'usleep'             , 1000000);
        array_default($params, 'verbose'            , true);

        /*
         * Required parameters
         */
        if(empty($params['source'])) {
            throw new CoreException(tr('rsync(): No source specified'), 'not-specified');
        }

        if(empty($params['target'])) {
            throw new CoreException(tr('rsync(): No target specified'), 'not-specified');
        }

        if($params['source'] == $params['target']) {
            throw new CoreException(tr('rsync(): Specified source and target are the same'), 'not-specified');
        }

        /*
         * See if we can auto build a source SSH command from the specified
         * source file
         */
        foreach(array('source', 'target') as &$item) {
            try{
                $server = Strings::until($params[$item], ':', 0, 0, true);

                if($server) {
                    if(isset($remote)) {
                        throw new CoreException(tr('rsync(): Both source and target are on remote servers. One of the two must be local'), 'invalid');
                    }

                    /*
                     * Source is a server, see if we know it
                     */
                    $remote                  = true;
                    $server                  = servers_like($server);
                    $server                  = servers_get($server);
                    $server['identity_file'] = servers_create_identity_file($server);

                    if($server) {
                        if($server['ssh_accounts_id']) {
                            /*
                             * Yay, we know the server, set the parameters!
                             */
                            $ssh = ssh_build_command($server, array('ssh_command'    => 'ssh',
                                                                    'no_user_server' => true));
                            $params[$item] = $server['username'].'@'.$params[$item];
                        }
                    }

                    /*
                     * Ensure this is not executed on ROOT or part of ROOT
                     */
                    switch($server['domain']) {
                        case '':
                            // FALLTHROUGH
                        case 'localhost':
                            foreach(array('source', 'target') as $subitem) {
                                if(str_exists($params[$subitem], ':')) {
                                    /*
                                     * We're syncing to THIS server, are we not
                                     * syncing to ROOT or its parents somehow?
                                     */
                                    try{
                                        if(str_exists(ROOT, linux_realpath($server, Strings::from($params[$subitem], ':')))) {
                                            throw new CoreException(tr('rsync(): Specified remote ":subitem" path ":path" is ROOT or parent of ROOT', array(':path' => $params[$subitem], ':subitem' => $subitem)), 'invalid');
                                        }

                                    }catch(Exception $e) {
                                        if($e->getRealCode() !== 'not-exists') {
                                            /*
                                             * If the target path would not exist we'd be okay
                                             */
                                            throw $e;
                                        }
                                    }

                                } else {
                                    if(str_exists(ROOT, realpath($params[$subitem]))) {
                                        throw new CoreException(tr('rsync(): Specified local ":subitem" path ":path" is ROOT or parent of ROOT', array(':path' => $params[$subitem], ':subitem' => $subitem)), 'invalid');
                                    }
                                }
                            }
                    }

                }

            }catch(Exception $e) {
                switch($e->getRealCode()) {
                    case 'not-exists':
                        throw new CoreException(tr('rsync(): Specified ":item" server ":server" does not exist', array(':item' => $item, ':server' => Strings::until($params['source'], ':', 0, 0, true))), $e);

                    default:
                        throw $e;
                }
            }
        }

        unset($item);

        if(isset($remote)) {
            if($params['ssh_options']) {
                throw new CoreException(tr('rsync(): Specified ":item" server ":server" is a registered server with its own ssh_options, yet "ssh_options" was also specified', array(':item' => $item, ':server' => Strings::until($params['source'], ':', 0, 0, true))), $e);
            }

        } elseif($params['ssh_options']) {
            $ssh = ssh_build_options($params['ssh_options']);
        }

        if(isset($ssh)) {
            $arguments[] = '-e';
            $arguments[] = $ssh;
        }

        if($params['archive']) {
            $arguments[] = '-a';
        }

        if($params['checksum']) {
            $arguments[] = '-c';
        }

        if($params['compression']) {
            $arguments[] = '-z';
        }

        if($params['remove_source_files']) {
            $arguments[] = '--remove-source-files';
        }

        if($params['inplace']) {
            $arguments[] = '--inplace';
        }

        if($params['delete']) {
            $arguments[] = '--delete';
        }

        if($params['exclude']) {
            if(!is_array($params['exclude'])) {
                $params['exclude'] = array($params['exclude']);
            }

            foreach($params['exclude'] as $exclude) {
                $arguments[] = '--exclude';
                $arguments[] = $exclude;
            }
        }

        if($params['force']) {
            $arguments[] = '--force';
        }

        if($params['group']) {
            $arguments[] = '-g';
        }

        if($params['links']) {
            $arguments[] = '-l';
        }

        if($params['owner']) {
            $arguments[] = '-o';
        }

        if($params['permissions']) {
            $arguments[] = '-p';
        }

        if($params['progress']) {
            $arguments[] = '--progress';
        }

        if($params['recursive']) {
            $arguments[] = '-r';
        }

        if($params['remote_rsync']) {
            $arguments[] = '--rsync-path="'.$params['remote_rsync'].'"';
        }

        if($params['super']) {
            $arguments[] = '--super';
        }

        if($params['time']) {
            $arguments[] = '-t';
        }

        if($params['verbose']) {
            $arguments[] = '-v';
        }

        $break       = true;
        $arguments[] = $params['source'];
        $arguments[] = $params['target'];

        if($params['monitor_task'] or $params['monitor_pid']) {
            /*
             * We need to monitor tasks so we need to cycle at least twice
             */
            $break = false;
        }

        while(true) {
            log_console(tr('Rsyncing from ":source" to ":target"', array(':source' => $params['source'], ':target' => $params['target'])), 'cyan');

            $results = safe_exec(array('function'     => $params['function'],
                                       'background'   => $params['background'],
                                       'ok_exitcodes' => $params['exitcodes'],
                                       'commands'     => array('rsync', $arguments)));

            if(!empty($break)) {
                /*
                 * We're done!
                 */
                break;
            }

            if($params['monitor_task']) {
                /*
                 * Monitor the specified task to see if it is still running. While
                 * it is running, we do not stop either.
                 */
                load_libs('tasks');

                if(!is_natural($params['monitor_task'])) {
                    throw new CoreException(tr('rsync(): Specified monitor task ":task" is invalid', array(':task' => $params['monitor_task'])), 'invalid');
                }

                if(tasks_check_pid($params['monitor_task'])) {
                    log_console(tr('Task ":task" still running, continuing rsync cycle', array(':task' => $params['monitor_task'])), 'VERBOSE/cyan');

                } else {
                    /*
                     * The process is done, break the loop
                     */
                    log_console(tr('Task ":task" finished, doing one last file check before finishing', array(':task' => $params['monitor_task'])), 'cyan');
                    $break = true;
                }
            }

            if($params['monitor_pid']) {
                /*
                 * Monitor the specified process to see if it is still running.
                 * While it is running, we do not stop either.
                 */
                if(!is_natural($params['monitor_pid'])) {
                    throw new CoreException(tr('rsync(): Specified process id ":pid" is invalid', array(':pid' => $params['monitor_pid'])), 'invalid');
                }

                if(cli_pid($params['monitor_pid'])) {
                    log_console(tr('Process":pid" still running, continuing rsync cycle', array(':pid' => $params['monitor_pid'])), 'VERBOSE/cyan');

                } else {
                    /*
                     * The process is done, break the loop
                     */
                    log_console(tr('Process ":pid" finished, doing one last file check before finishing', array(':pid' => $params['monitor_pid'])), 'cyan');
                    $break = true;
                }
            }

            usleep($params['usleep']);
        }

    }catch(Exception $e) {
        /*
         * Give nice rsync errors
         */
        switch($e->getRealCode()) {
            case 0:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Success"'), $e);

            case 1:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Syntax or usage error"'), $e);

            case 2:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Protocol incompatibility"'), $e);

            case 3:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Errors selecting input/output files, dirs"'), $e);

            case 4:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Requested action not supported: an attempt was made to manipulate 64-bit files on a platform that cannot support them; or  an  option was specified that is supported by the client and not by the server."'), $e);

            case 5:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Error starting client-server protocol"'), $e);

            case 6:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Daemon unable to append to log-file"'), $e);

            case 10:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Error in socket I/O"'), $e);

            case 11:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Error in file I/O"'), $e);

            case 12:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Error in rsync protocol data stream"'), $e);

            case 13:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Errors with program diagnostics"'), $e);

            case 14:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Error in IPC code"'), $e);

            case 20:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Received SIGUSR1 or SIGINT"'), $e);

            case 21:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Some error returned by waitpid()"'), $e);

            case 22:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Error allocating core memory buffers"'), $e);

            case 23:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Partial transfer due to error"'), $e);

            case 24:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Partial transfer due to vanished source files"'), $e);

            case 25:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "The --max-delete limit stopped deletions"'), $e);

            case 30:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Timeout in data send/receive"'), $e);

            case 35:
                $e->makeWarning(true);
                throw new CoreException(tr('rsync(): Rsync failed with "Timeout waiting for daemon connection""'), $e);
        }

        throw new CoreException('rsync(): Failed', $e);
    }
}
?>
