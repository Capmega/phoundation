<?php
/*
 * libs library
 *
 * This library contains functions to manage the available BASE libraries
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Return an array of all available library files
 */
function libs_list(){
    try{
        return scandir(ROOT.'libs');

    }catch(Exception $e){
        throw new BException('libs_list(): Failed', $e);
    }
}



/*
 * Return the amount of available libraries
 */
function libs_count(){
    try{
        return count(libs_list());

    }catch(Exception $e){
        throw new BException('libs_count(): Failed', $e);
    }
}



/*
 * Execute the specified callback function for each available library file
 */
function libs_exec($callback){
    try{
        return file_tree_execute(array('path' => ROOT.'libs'));

    }catch(Exception $e){
        throw new BException('libs_exec(): Failed', $e);
    }
}
?>
