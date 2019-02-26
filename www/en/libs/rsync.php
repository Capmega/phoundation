<?php
/*
 * Rsync library
 *
 * This library is an rsync frontend and contains functions to
 * sync directories from local server with remote server
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */


/*
 * Front end for the command line rsync command. Performs rsync with the specified parameters
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
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
function rsync($params){
    load_libs('servers');

    try{
        /*
         * The optional parameters
         */
        array_params($params);
        array_default($params, 'append'             , false);
        array_default($params, 'append_verify'      , false);
        array_default($params, 'archive'            , true);
        array_default($params, 'function'           , (PLATFORM_CLI ? 'passthru' : null));
        array_default($params, 'checksum'           , true);
        array_default($params, 'compression'        , true);
        array_default($params, 'delete'             , true);
        array_default($params, 'force'              , true);
        array_default($params, 'group'              , true);
        array_default($params, 'links'              , true);
        array_default($params, 'owner'              , true);
        array_default($params, 'permissions'        , true);
        array_default($params, 'progress'           , true);
        array_default($params, 'recursive'          , true);
        array_default($params, 'super'              , false);
        array_default($params, 'time'               , true);
        array_default($params, 'inplace'            , true);
        array_default($params, 'remove_source_files', true);
        array_default($params, 'port'               , null);
        array_default($params, 'ssh_options'        , null);
        array_default($params, 'remote_rsync'       , false);

        /*
         * Required parameters
         */
        if(empty($params['source'])){
            throw new BException(tr('rsync(): No source specified'), 'not-specified');
        }

        if(empty($params['target'])){
            throw new BException(tr('rsync(): No target specified'), 'not-specified');
        }

        if($params['source'] == $params['target']){
            throw new BException(tr('rsync(): Specified source and target are the same'), 'not-specified');
        }

        /*
         * See if we can auto build a source SSH command from the specified
         * source file
         */
        foreach(array('source', 'target') as &$item){
            try{
                $server = str_until($params[$item], ':', 0, 0, true);

                if($server){
                    if(isset($remote)){
                        throw new BException(tr('rsync(): Both source and target are on remote servers. One of the two must be local'), 'invalid');
                    }

                    /*
                     * Source is a server, see if we know it
                     */
                    $remote                  = true;
                    $server                  = servers_like($server);
                    $server                  = servers_get($server);
                    $server['identity_file'] = servers_create_identity_file($server);

                    if($server){
                        if($server['ssh_accounts_id']){
                            /*
                             * Yay, we know the server, set the parameters!
                             */
                            $ssh           = ssh_build_command($server, 'ssh', true);
                            $params[$item] = $server['username'].'@'.$params[$item];
                        }
                    }
                }

            }catch(Exception $e){
                switch($e->getRealCode()){
                    case 'not-exists':
                        throw new BException(tr('rsync(): Specified ":item" server ":server" does not exist', array(':item' => $item, ':server' => str_until($params['source'], ':', 0, 0, true))), $e);

                    default:
                        throw $e;
                }
            }
        }

        unset($item);
        $command = 'rsync';

        if(isset($remote)){
            if($params['ssh_options']){
                throw new BException(tr('rsync(): Specified ":item" server ":server" is a registered server with its own ssh_options, yet "ssh_options" was also specified', array(':item' => $item, ':server' => str_until($params['source'], ':', 0, 0, true))), $e);
            }

        }elseif($params['ssh_options']){
            $ssh = ssh_build_options($params['ssh_options']);
        }

        if(isset($ssh)){
            $command .= ' -e "'.$ssh.'" ';
        }

        if($params['archive']){
            $command .= ' -a ';
        }

        if($params['checksum']){
            $command .= ' -c ';
        }

        if($params['compression']){
            $command .= ' -z ';
        }

        if($params['remove_source_files']){
            $command .= ' --remove-source-files';
        }

        if($params['inplace']){
            $command .= ' --inplace';
        }

        if($params['delete']){
            $command .= ' --delete ';
        }

        if($params['force']){
            $command .= ' --force ';
        }

        if($params['group']){
            $command .= ' -g ';
        }

        if($params['links']){
            $command .= ' -l ';
        }

        if($params['owner']){
            $command .= ' -o ';
        }

        if($params['permissions']){
            $command .= ' -p ';
        }

        if($params['progress']){
            $command .= ' --progress ';
        }

        if($params['recursive']){
            $command .= ' -r ';
        }

        if($params['remote_rsync']){
            $command .= ' --rsync-path="'.$params['remote_rsync'].'" ';
        }

        if($params['super']){
            $command .= ' --super ';
        }

        if($params['time']){
            $command .= ' -t ';
        }

        $command .= ' '.$params['source'].' '.$params['target'];

        log_file($command, 'rsync');

        $results = safe_exec($command, null, true, $params['function']);

    }catch(Exception $e){
        /*
         * Give nice rsync errors
         */
        switch($e->getRealCode()){
            case 0:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Success"'), $e);

            case 1:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Syntax or usage error"'), $e);

            case 2:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Protocol incompatibility"'), $e);

            case 3:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Errors selecting input/output files, dirs"'), $e);

            case 4:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Requested action not supported: an attempt was made to manipulate 64-bit files on a platform that cannot support them; or  an  option was specified that is supported by the client and not by the server."'), $e);

            case 5:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Error starting client-server protocol"'), $e);

            case 6:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Daemon unable to append to log-file"'), $e);

            case 10:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Error in socket I/O"'), $e);

            case 11:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Error in file I/O"'), $e);

            case 12:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Error in rsync protocol data stream"'), $e);

            case 13:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Errors with program diagnostics"'), $e);

            case 14:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Error in IPC code"'), $e);

            case 20:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Received SIGUSR1 or SIGINT"'), $e);

            case 21:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Some error returned by waitpid()"'), $e);

            case 22:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Error allocating core memory buffers"'), $e);

            case 23:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Partial transfer due to error"'), $e);

            case 24:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Partial transfer due to vanished source files"'), $e);

            case 25:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "The --max-delete limit stopped deletions"'), $e);

            case 30:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Timeout in data send/receive"'), $e);

            case 35:
                $e->makeWarning(true);
                throw new BException(tr('rsync(): Rsync failed with "Timeout waiting for daemon connection""'), $e);
        }

        throw new BException('rsync(): Failed', $e);
    }
}
?>
