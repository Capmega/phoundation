<?php
    global $_CONFIG;

    /*
     * We're doing a forced init from shell. Forced init will
     * basically set database version to 0 BY DROPPING THE FUCKER SO BE CAREFUL!
     *
     * Forced init is NOT allowed on production (for obvious safety reasons, doh!)
     */
    if($_CONFIG['production']){
        throw new BException('sql_init(): For safety reasons, init force is NOT allowed on production environment! Please drop the database using "./scripts/base/init drop" or in the mysql console with "DROP DATABASE \''.str_log($_CONFIG['db'][$connector]['db']).'\'"and continue with a standard init', 'forcedenied');
    }

    if(!str_is_version(FORCE)){
        if(!is_bool(FORCE)){
            throw new BException('sql_init(): Invalid "force" sub parameter "'.str_log(FORCE).'" specified. "force" can only be followed by a valid init version number', 'invalidforce');
        }

        /*
         * Dump database, and recreate it
         */
        $core->sql[$connector]->query('DROP   DATABASE IF EXISTS `'.$_CONFIG['db'][$connector]['db'].'`');
        $core->sql[$connector]->query('CREATE DATABASE           `'.$_CONFIG['db'][$connector]['db'].'` DEFAULT CHARSET="'.$_CONFIG['db'][$connector]['charset'].'" COLLATE="'.$_CONFIG['db'][$connector]['collate'].'";');
        $core->sql[$connector]->query('USE                       `'.$_CONFIG['db'][$connector]['db'].'`');
    }
?>
