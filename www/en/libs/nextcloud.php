<?php
/*
 * Nextcloud library
 *
 * This library is a front-end library to control nextcloud installations on registered servers over SSH
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <license@capmega.com>
 * @category Function reference
 * @package nextcloud
 *
 * Nextcloud 13.0.5

Available commands:
  _completion                         BASH completion hook.
  check                               check dependencies of the server environment
  help                                Displays help for a command
  list                                Lists commands
  status                              show some status information
  upgrade                             run upgrade routines after installation of a new release. The release has to be installed before.
 activity
  activity:send-mails                 Sends the activity notification mails
 app
  app:check-code                      check code to be compliant
  app:disable                         disable an app
  app:enable                          enable an app
  app:getpath                         Get an absolute path to the app directory
  app:install                         install an app
  app:list                            List all available apps
 audioplayer
  audioplayer:reset                   reset audio player library
  audioplayer:scan                    scan for new audio files
 background
  background:ajax                     Use ajax to run background jobs
  background:cron                     Use cron to run background jobs
  background:webcron                  Use webcron to run background jobs
 config
  config:app:delete                   Delete an app config value
  config:app:get                      Get an app config value
  config:app:set                      Set an app config value
  config:import                       Import a list of configs
  config:list                         List all configs
  config:system:delete                Delete a system config value
  config:system:get                   Get a system config value
  config:system:set                   Set a system config value
 dav
  dav:create-addressbook              Create a dav addressbook
  dav:create-calendar                 Create a dav calendar
  dav:remove-invalid-shares           Remove invalid dav shares
  dav:sync-birthday-calendar          Synchronizes the birthday calendar
  dav:sync-system-addressbook         Synchronizes users to the system addressbook
 db
  db:add-missing-indices              Add missing indices to the database tables
  db:convert-filecache-bigint         Convert the ID columns of the filecache to BigInt
  db:convert-mysql-charset            Convert charset of MySQL/MariaDB to use utf8mb4
  db:convert-type                     Convert the Nextcloud database to the newly configured one
 deck
  deck:export                         Export a JSON dump of user data
 encryption
  encryption:change-key-storage-root  Change key storage root
  encryption:decrypt-all              Disable server-side encryption and decrypt all files
  encryption:disable                  Disable encryption
  encryption:enable                   Enable encryption
  encryption:encrypt-all              Encrypt all files for all users
  encryption:list-modules             List all available encryption modules
  encryption:set-default-module       Set the encryption default module
  encryption:show-key-storage-root    Show current key storage root
  encryption:status                   Lists the current status of encryption
 federation
  federation:sync-addressbooks        Synchronizes addressbooks of all federated clouds
 files
  files:cleanup                       cleanup filecache
  files:scan                          rescan filesystem
  files:scan-app-data                 rescan the AppData folder
  files:transfer-ownership            All files and folders are moved to another user - shares are moved as well.
 files_frommail
  files_frommail:address              manage the linked groups
 group
  group:adduser                       add a user to a group
  group:list                          list configured groups
  group:removeuser                    remove a user from a group
 integrity
  integrity:check-app                 Check integrity of an app using a signature.
  integrity:check-core                Check integrity of core code using a signature.
  integrity:sign-app                  Signs an app using a private key.
  integrity:sign-core                 Sign core using a private key.
 l10n
  l10n:createjs                       Create javascript translation files for a given app
 ldap
  ldap:check-user                     checks whether a user exists on LDAP.
  ldap:create-empty-config            creates an empty LDAP configuration
  ldap:delete-config                  deletes an existing LDAP configuration
  ldap:search                         executes a user or group search
  ldap:set-config                     modifies an LDAP configuration
  ldap:show-config                    shows the LDAP configuration
  ldap:show-remnants                  shows which users are not available on LDAP anymore, but have remnants in Nextcloud.
  ldap:test-config                    tests an LDAP configuration
 log
  log:file                            manipulate logging backend
  log:manage                          manage logging configuration
 maintenance
  maintenance:data-fingerprint        update the systems data-fingerprint after a backup is restored
  maintenance:mimetype:update-db      Update database mimetypes and update filecache
  maintenance:mimetype:update-js      Update mimetypelist.js
  maintenance:mode                    set maintenance mode
  maintenance:repair                  repair this installation
  maintenance:theme:update            Apply custom theme changes
  maintenance:update:htaccess         Updates the .htaccess file
 migrations
  migrations:execute                  Execute a single migration version manually.
  migrations:generate
  migrations:generate-from-schema
  migrations:migrate                  Execute a migration to a specified version or the latest available version.
  migrations:status                   View the status of a set of migrations.
 notification
  notification:generate               Generate a notification for the given user
 preview
  preview:delete_old                  Delete old preview folder (pre NC11)
  preview:generate-all                Generate previews
  preview:pre-generate                Pre generate previews
 ransomware_protection
  ransomware_protection:block         Block a user from syncing further files
 security
  security:certificates               list trusted certificates
  security:certificates:import        import trusted certificate
  security:certificates:remove        remove trusted certificate
 sharing
  sharing:cleanup-remote-storages     Cleanup shared storage entries that have no matching entry in the shares_external table
 trashbin
  trashbin:cleanup                    Remove deleted files
  trashbin:expire                     Expires the users trashbin
 twofactorauth
  twofactorauth:disable               Disable two-factor authentication for a user
  twofactorauth:enable                Enable two-factor authentication for a user
 usage-report
  usage-report:generate               Prints a CVS entry with some usage information of the user:
userId,date,assignedQuota,usedQuota,numFiles,numShares,numUploads,numDownloads
"admin","2017-09-18T09:00:01+00:00",5368709120,786432000,1024,23,1400,5678
 user
  user:add                            adds a user
  user:delete                         deletes the specified user
  user:disable                        disables the specified user
  user:enable                         enables the specified user
  user:info                           show user info
  user:lastseen                       shows when the user was logged in last time
  user:list                           list configured users
  user:report                         shows how many users have access
  user:resetpassword                  Resets the password of the named user
  user:setting                        Read and modify user settings
 versions
  versions:cleanup                    Delete versions
  versions:expire                     Expires the users file versions
 *
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
 * @package nextcloud
 * @version 2.4.11: Added function and documentation
 *
 * @return void
 */
function nextcloud_library_init(){
    try{
        load_libs('services');

    }catch(Exception $e){
        throw new CoreException('nextcloud_library_init(): Failed', $e);
    }
}



/*
 * List all registered and available nextcloud servers
 *
 * This function returns an array with the domain names of all registered and available nextcloud servers. The list, once requested, will be cached and each subsequent call will return the same list of servers, even if in database the list has changed, unless $force is specified as true. If $force is specified, the cache will be ignored and the list will again be read from disk
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 * @see services_list_servers()
 *
 * @param boolean $server If set to true, will only return the default nextcloud server. If set to a domain, will return the domain IF that domain supports the specified service
 * @param boolean $force If set to true, will ignore cache and re-read the list from the database
 * @return array a list of all registered and available nextcloud server domains
 */
function nextcloud_list_servers($server = null, $force = false){
    static $servers;

    try{
        if(empty($servers) and !$force){
            $servers = services_list_servers('nextcloud', $server);
        }

        return $servers;

    }catch(Exception $e){
        throw new CoreException('nextcloud_list_servers(): Failed', $e);
    }
}



/*
 * Select the nextcloud server on which to execute the updates
 *
 * This function returns an array with the domain names of all registered and available nextcloud servers. The list, once requested, will be cached and each subsequent call will return the same list of servers, even if in database the list has changed, unless $force is specified as true. If $force is specified, the cache will be ignored and the list will again be read from disk
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param boolean $server If set to true, will only return the default nextcloud server. If set to a domain, will return the domain IF that domain supports the specified service
 * @return array a list of all registered and available nextcloud server domains
 */
function nextcloud_select_server($server, $force = false){
    try{
        $servers = nextcloud_list_servers($server, $force);

        /*
         * No server specified, so we should have only the default nextcloud server
         */
        $server = array_shift($servers);
        return $server;

    }catch(Exception $e){
        throw new CoreException('nextcloud_select_server(): Failed', $e);
    }
}



/*
 * Select the nextcloud server on which to execute the updates
 *
 * This function returns an array with the domain names of all registered and available nextcloud servers. The list, once requested, will be cached and each subsequent call will return the same list of servers, even if in database the list has changed, unless $force is specified as true. If $force is specified, the cache will be ignored and the list will again be read from disk
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @return array a list of all registered and available nextcloud server domains
 */
function nextcloud_exec($server, $params){
    try{
        $nextcloud = nextcloud_select_server($server);

        foreach($params['commands'] as $key => &$value){
            if(!($key % 2)){
                /*
                 * This is a command
                 */
                $value = slash($nextcloud['path']).$command;
            }
        }

        return servers_exec($nextcloud['domain'], $params);

    }catch(Exception $e){
        throw new CoreException('nextcloud_exec(): Failed', $e);
    }
}



/*
 * Create a user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $user
 * @param mixed $server
 * @return
 */
function nextcloud_users_add($user, $server = null){
    try{
        $retval = nextcloud_exec($server, array('commands' => array('export', array('')),
                                                                    'php'   , array('occ', 'user:add', '--password-from-env', '--display-name' => $user['nickname'], $user['id'])));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('nextcloud_users_add(): Failed', $e);
    }
}



/*
 * Delete the specified user from the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_delete($server, $user){
    try{
        $retval = nextcloud_exec($server, array('commands' => array('php', array('occ', 'user:delete', $user['username']))));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('nextcloud_delete(): Failed', $e);
    }
}



/*
 * Disable the specified existing user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_disable($server, $user){
    try{
        $retval = nextcloud_exec($server, array('commands' => array('php', array('occ', 'user:disable', $user['username']))));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('nextcloud_users_disable(): Failed', $e);
    }
}



/*
 * Enable the specified existing user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_enable($server, $user){
    try{
        $retval = nextcloud_exec($server, array('commands' => array('php', array('occ', 'user:enable', $user['username']))));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('nextcloud_users_enable(): Failed', $e);
    }
}



/*
 * Get and return information about the speficied user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_info($server, $user){
    try{
        $retval = nextcloud_exec($server, array('commands' => array('php', array('occ', 'user:info', $user['username']))));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('nextcloud_users_info(): Failed', $e);
    }
}



/*
 * Get and return last seen information about the speficied user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_last_seen($server, $user){
    try{
        $retval = nextcloud_exec($server, array('commands' => array('export', array('')),
                                                                    'php'   , array('occ', 'user:disable', '--password-from-env', $user['username'])));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('nextcloud_users_last_seen(): Failed', $e);
    }
}



/*
 * List the available users on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @return
 */
function nextcloud_users_list($server){
    try{
        $retval = nextcloud_exec($server, array('commands' => array('php', array('occ', 'user:list'))));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('nextcloud_users_list(): Failed', $e);
    }
}



/*
 * Get and return a list of how many users have access on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @return
 */
function nextcloud_users_report($server){
    try{
        $retval = nextcloud_exec($server, array('commands' => array('php', array('occ', 'user:report'))));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('nextcloud_users_report(): Failed', $e);
    }
}



/*
 * Reset the password for the specified user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @param mixed $password
 * @return
 */
function nextcloud_users_reset_password($server, $user, $password){
    try{
        $retval = nextcloud_exec($server, array('commands' => array('export', array('')),
                                                                    'php'   , array('occ', 'user:resetpassword', '--password-from-env', $user['username'])));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('nextcloud_users_reset_password(): Failed', $e);
    }
}



/*
 * Read and return, or modify settings for the specified user on the specifed nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @param array settings
 * @return
 */
function nextcloud_users_setting($server, $user, $settings = null){
    try{
        $retval = nextcloud_exec($server, array('commands' => array('php', array('occ', 'user:setting', $user['username']))));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('nextcloud_users_setting(): Failed', $e);
    }
}



/*
 * Create a user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_check_user_ldap($server, $user){
    try{

    }catch(Exception $e){
        throw new CoreException('nextcloud_check_user_ldap(): Failed', $e);
    }
}



/*
 * Add the specified user to the specified group on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 * @see nextcloud_remove_user_from_group()
 *
 * @param mixed $server
 * @param mixed $user
 * @param mixed $group
 * @return
 */
function nextcloud_add_user_to_group($server, $user, $group){
    try{

    }catch(Exception $e){
        throw new CoreException('nextcloud_add_user_to_group(): Failed', $e);
    }
}



/*
 * Remove the specified user from the specified group on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud nextcloud
 * @see nextcloud_add_user_to_group()
 *
 * @param mixed $server
 * @param mixed $user
 * @param mixed $group
 * @return
 */
function nextcloud_remove_user_from_group($server, $user, $group){
    try{

    }catch(Exception $e){
        throw new CoreException('nextcloud_remove_user_from_group(): Failed', $e);
    }
}
?>
