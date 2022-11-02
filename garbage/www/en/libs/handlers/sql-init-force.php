<?php
global $_CONFIG;

/*
 * We're doing a forced init from shell. Forced init will
 * basically set database version to 0 BY DROPPING THE FUCKER SO BE CAREFUL!
 *
 * Forced init is NOT allowed on production (for obvious safety reasons, doh!)
 */
if (Debug::production()) {
    throw new CoreException(tr('sql_init(): For safety reasons, init force is NOT allowed on production environment! Please drop the database using "./scripts/base/init drop" or in the mysql console with "DROP DATABASE :db"and continue with a standard init', array(':db' => $_CONFIG['db'][$connector]['db'])), 'forcedenied');
}

if (!str_is_version(FORCE)) {
    if (!is_bool(FORCE)) {
        throw new CoreException(tr('sql_init(): Invalid "force" sub parameter ":force" specified. "force" can only be followed by a valid init version number', array(':force' => FORCE)), 'invalidforce');
    }

    /*
     * Dump database, and recreate it
     */
    sql_connect($connector);

    $core->sql['core']->query('DROP   DATABASE IF EXISTS `'.$_CONFIG['db']['core']['db'].'`');
    $core->sql['core']->query('CREATE DATABASE           `'.$_CONFIG['db']['core']['db'].'` DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
    $core->sql['core']->query('USE                       `'.$_CONFIG['db']['core']['db'].'`');
}
