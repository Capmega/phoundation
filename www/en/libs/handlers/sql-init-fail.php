<?php
    /*
     *
     */
    switch($e->getCode()){
        case 1049:
            if(empty($connector)){
                throw new BException(tr('sql_init(): Database reported that database does not exist, but no connector data is available'), 'not-exist');
            }

            if(!empty($retry)){
                static $retry = true;
                global $_CONFIG;

                try{
                    $core->sql['core']->query('DROP DATABASE IF EXISTS `'.$connector['db'].'`;');
                    $core->sql['core']->query('CREATE DATABASE         `'.$connector['db'].'` DEFAULT CHARSET="'.$connector['charset'].'" COLLATE="'.$connector['collate'].'";');
                    $core->sql['core']->query('USE                     `'.$connector['db'].'`');
                    return true;

                }catch(Exception $e){
                    throw new BException('sql_init(): Failed', $e);
                }

                throw $e;
            }

            break;

        case 'not-specified':
            throw new BException('sql_init(): Failed', $e);
    }

    /*
     * From here it is probably connector issues
     */
    $e = new BException('sql_init(): Failed', $e);

    if(!is_string($connector_name)){
        throw new BException(tr('sql_init(): Specified database connector ":connector" is invalid, must be a string', array(':connector' => $connector_name)), 'invalid');
    }

    if(empty($_CONFIG['db'][$connector_name])){
        throw new BException(tr('sql_init(): Specified database connector ":connector" has not been configured', array(':connector' => $connector_name)), 'not-exists');
    }

    try{
        return sql_error($e, $_CONFIG['db'][$connector_name], null, isset_get($core->sql[$connector_name]));

    }catch(Exception $e){
        throw new BException('sql_init(): Failed', $e);
    }
?>
