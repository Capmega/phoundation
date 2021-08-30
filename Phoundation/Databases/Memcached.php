<?php
/*
 * Memcached library
 *
 * This library file contains functions to access memcached. It supports namespaces by keeping track of all variables
 * with namespaces in a separate array that contains the name of that namespace. This is VERY far from ideal, but the
 * best it can do. If this behaviour is not desired, then simply ensure that all keys have no namespace specified
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function memcached_library_init(){
    try{
        if(!class_exists('Memcached')){
            throw new BException(tr('memcached_library_init(): php module "memcached" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo sudo apt-get -y install php5-memcached; sudo php5enmod memcached" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-memcached" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'not_available');
        }

    }catch(Exception $e){
        throw new BException('memcached_library_init(): failed', $e);
    }
}



/*
 * Connect to the memcached server
 */
function memcached_connect(){
    global $_CONFIG, $core;

    try{
        if(empty($core->register['memcached'])){
            /*
             * Memcached disabled?
             */
            if(!$_CONFIG['memcached']){
                $core->register['memcached'] = false;
                log_file('memcached_connect(): Not using memcached, its disabled by configuration $_CONFIG[memcached]', 'yellow');

            }else{
                $failed                      = 0;
                $core->register['memcached'] = new Memcached;

                /*
                 * Connect to all memcached servers, but only if no servers were added yet
                 * (this should normally be the case)
                 */
                if(!$core->register['memcached']->getServerList()){
                    $core->register['memcached']->addServers($_CONFIG['memcached']['servers']);
                }

                /*
                 * Check connection status of memcached servers
                 * (To avoid memcached servers being down and nobody knows about it)
                 */
        //:TODO: Maybe we should check this just once every 10 connects or so? is it really needed?
                try{
                    foreach($core->register['memcached']->getStats() as $server => $server_data){
                        if($server_data['pid'] < 0){
                            /*
                             * Could not connect to this memcached server. Notify, and remove from the connections list
                             */
                            $failed++;

                            notify(array('code'    => 'warning/not-available',
                                         'groups'  => 'developers',
                                         'title'   => tr('Memcached server not available'),
                                         'message' => tr('memcached_connect(): Failed to connect to memcached server ":server"', array(':server' => $server))));
                        }
                    }

                }catch(Exception $e){
                    /*
                     * Server status check failed, I think its safe
                     * to assume that no memcached server is working.
                     * Fake "all severs failed" so that memcached won't
                     * be used
                     */
                    $failed = count($_CONFIG['memcached']['servers']);
                }

                if($failed >= count($_CONFIG['memcached']['servers'])){
                    /*
                     * All memcached servers failed to connect!
                     * Send error notification
                     */
                    notify(array('code'    => 'not-available',
                                 'groups'  => 'developers',
                                 'title'   => tr('Memcached server not available'),
                                 'message' => tr('memcached_connect(): Failed to connect to all ":count" memcached servers', array(':server' => count($_CONFIG['memcached']['servers'])))));

                    return false;
                }
            }
        }

        return $core->register['memcached'];

    }catch(Exception $e){
        throw new BException('memcached_connect(): failed', $e);
    }
}



/*
 *
 */
function memcached_put($value, $key, $namespace = null, $expiration_time = null){
    global $_CONFIG, $core;

    try{
        if(!memcached_connect()){
            return false;
        }

        if($namespace){
            $namespace = memcached_namespace($namespace).'_';
        }

        if($expiration_time === null){
            /*
             * Use default cache expire time
             */
            $expiration_time = $_CONFIG['memcached']['expire_time'];
        }

        $core->register['memcached']->set($_CONFIG['memcached']['prefix'].memcached_namespace($namespace).$key, $value, $expiration_time);
        log_console(tr('memcached_put(): Wrote key ":key"', array(':key' => $_CONFIG['memcached']['prefix'].memcached_namespace($namespace).$key)), 'VERYVERBOSE/green');

        return $value;

    }catch(Exception $e){
        throw new BException('memcached_put(): failed', $e);
    }
}



/*
 *
 */
function memcached_add($value, $key, $namespace = null, $expiration_time = null){
    global $_CONFIG, $core;

    try{
        if(!memcached_connect()){
            return false;
        }

        if($namespace){
            $namespace = memcached_namespace($namespace).'_';
        }

        if($expiration_time === null){
            /*
             * Use default cache expire time
             */
            $expiration_time = $_CONFIG['memcached']['expire_time'];
        }

        if(!$core->register['memcached']->add($_CONFIG['memcached']['prefix'].memcached_namespace($namespace).$key, $value, $expiration_time)){
// :TODO: Exception?
        }

        log_console(tr('memcached_add(): Added key ":key"', array(':key' => $_CONFIG['memcached']['prefix'].memcached_namespace($namespace).$key)), 'VERYVERBOSE/green');
        return $value;

    }catch(Exception $e){
        throw new BException('memcached_add(): failed', $e);
    }
}



/*
 *
 */
function memcached_replace($value, $key, $namespace = null, $expiration_time = null){
    global $_CONFIG, $core;

    try{
        if(!memcached_connect()){
            return false;
        }

        if($namespace){
            $namespace = memcached_namespace($namespace).'_';
        }

        if($expiration_time === null){
            /*
             * Use default cache expire time
             */
            $expiration_time = $_CONFIG['memcached']['expire_time'];
        }

        if(!$core->register['memcached']->replace($_CONFIG['memcached']['prefix'].memcached_namespace($namespace).$key, $value, $expiration_time)){

        }

        return $value;

    }catch(Exception $e){
        throw new BException('memcached_replace(): failed', $e);
    }
}



/*
 *
 */
function memcached_get($key, $namespace = null){
    global $_CONFIG, $core;

    try{
        if(!memcached_connect()){
            return false;
        }

        $data = $core->register['memcached']->get($_CONFIG['memcached']['prefix'].memcached_namespace($namespace).$key);

        if($data){
            log_console(tr('memcached_get(): Returned data for key ":key"', array(':key' => $_CONFIG['memcached']['prefix'].memcached_namespace($namespace).$key)), 'VERYVERBOSE/green');

        }else{
            log_console(tr('memcached_get(): Found no data for key ":key"', array(':key' => $_CONFIG['memcached']['prefix'].memcached_namespace($namespace).$key)), 'VERYVERBOSE/green');
        }

        return $data;

    }catch(Exception $e){
        throw new BException('memcached_get(): Failed', $e);
    }
}



/*
 * Delete the specified key or namespace
 */
function memcached_delete($key, $namespace = null){
    global $_CONFIG, $core;

    try{
        if(!memcached_connect()){
            return false;
        }

        if(!$key){
            if(!$namespace){

            }

            /*
             * Delete the entire namespace
             */
            return memcached_namespace($namespace, true);
        }

        return $core->register['memcached']->delete($_CONFIG['memcached']['prefix'].memcached_namespace($namespace).$key);

    }catch(Exception $e){
        throw new BException('memcached_delete(): Failed', $e);
    }
}



/*
 * clear the entire memcache
 */
function memcached_clear($delay = 0){
    global $_CONFIG, $core;

    try{
        if(!memcached_connect()){
            return false;
        }

        $core->register['memcached']->flush($delay);

    }catch(Exception $e){
        throw new BException('memcached_clear(): Failed', $e);
    }
}



/*
 * Increment the value of the specified key
 */
function memcached_increment($key, $namespace = null){
    global $_CONFIG, $core;

    try{
        if(!memcached_connect()){
            return false;
        }

        $core->register['memcached']->increment($_CONFIG['memcached']['prefix'].memcached_namespace($namespace).$key);

    }catch(Exception $e){
        throw new BException('memcached_increment(): Failed', $e);
    }
}



/*
 * Return a key for the namespace. We don't use the namespace itself as part of the key because
 * with an alternate key, its very easy to invalidate namespace keys by simply assigning a new
 * value to the namespace key
 */
function memcached_namespace($namespace, $delete = false){
    global $_CONFIG;
    static $keys = array();

    try{
        if(!$namespace or !$_CONFIG['memcached']['namespaces']){
            return '';
        }

        if(array_key_exists($namespace, $keys)){
            return $keys[$namespace];
        }

        $key = memcached_get('ns:'.$namespace);

        if(!$key){
            $key = (string) microtime(true);
            memcached_add($key, 'ns:'.$namespace);

        }elseif($delete){
            /*
             * "Delete" the key by incrementing (and so, changing) the value of the namespace key.
             * Since this will change the name of all keys using this namespace, they are no longer
             * accessible and with time will be dumped automatically by memcached to make space for
             * newer keys.
             */
            try{
                memcached_increment($namespace);
                $key = memcached_get('ns:'.$namespace);

            }catch(Exception $e){
                /*
                 * Increment failed, so in all probability the key did not exist. It could have been
                 * deleted by a parrallel process, for example
                 */
                switch($e->getCode()){
                    case '':
// :TODO: Implement correctly. For now, just notify
                    default:
                        notify($e);
                }
            }
        }

        $keys[$namespace] = $key;
        return $key;

    }catch(Exception $e){
        throw new BException('memcached_namespace(): Failed', $e);
    }
}



/*
 * Return statistics for memcached
 */
function memcached_stats(){
    global $core;

    try{
        if(!memcached_connect()){
            return false;
        }

        if(empty($core->register['memcached'])){
            /*
             * Not connected to a memcached server!
             */
            return null;
        }

        return $core->register['memcached']->getStats();

    }catch(Exception $e){
        throw new BException('memcached_stats(): Failed', $e);
    }
}