<?php
/*
 * Meta library
 *
 * Can store meta information about other database records
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Add specified action to meta history for the specified meta_id
 */
function meta_action($meta_id = null, $action = null, $data = null){
    try{
        if(!$meta_id){
            if(!$action){
                $action = 'create';
            }

            sql_query('INSERT INTO `meta` (`id`)
                       VALUES             (null)', null, 'core');

            $meta_id = sql_insert_id('core');

        }else{
            if(!is_numeric($meta_id)){
                throw new BException(tr('meta_action(): Invalid meta_id ":meta_id" specified', array(':meta_id' => $meta_id)), 'invalid');
            }
        }

        return meta_add_history($meta_id, $action, $data);

    }catch(Exception $e){
        throw new BException('meta_action(): Failed', $e);
    }
}



/*
 * Add specified action to meta history for the specified meta_id
 */
function meta_add_history($meta_id, $action, $data = null){
    try{
        sql_query('INSERT INTO `meta_history` (`createdby`, `meta_id`, `action`, `data`)
                   VALUES                     (:createdby , :meta_id , :action , :data )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => $meta_id,
                         ':action'    => not_empty($action, tr('Unknown')),
                         ':data'      => json_encode($data)), 'core');

        return $meta_id;

    }catch(Exception $e){
        throw new BException('meta_add_history(): Failed', $e);
    }
}



/*
 * Return array with all the history for the specified meta_id
 */
function meta_history($meta_id){
    try{
        $history = sql_list('SELECT    `meta_history`.`id`,
                                       `meta_history`.`createdby`,
                                       `meta_history`.`createdon`,
                                       `meta_history`.`action`,
                                       `meta_history`.`data`,

                                       `users`.`name`,
                                       `users`.`email`,
                                       `users`.`username`,
                                       `users`.`nickname`

                             FROM      `meta_history`

                             LEFT JOIN `users`
                             ON        `users`.`id` = `meta_history`.`createdby`

                             WHERE     `meta_history`.`meta_id` = :meta_id

                             ORDER BY  `meta_history`.`createdon` DESC, `meta_history`.`id` DESC ',

                             array(':meta_id' => $meta_id), false, 'core');

        return $history;

    }catch(Exception $e){
        throw new BException('meta_history(): Failed', $e);
    }
}



/*
 * Erase the meta entry
 * NOTE: Due to foreign key restraints, ensure that the referencing table entry
 * has been erased first!
 */
function meta_erase($meta_id){
    try{
        sql_query('DELETE FROM `meta_history` WHERE `meta_id` = :meta_id', array(':meta_id' => $meta_id), 'core');
        sql_query('DELETE FROM `meta`         WHERE `id`      = :id'     , array(':id'      => $meta_id), 'core');

        return $meta_id;

    }catch(Exception $e){
        throw new BException('meta_erase(): Failed', $e);
    }
}



/*
 *
 */
function meta_clear($meta_id, $views_only = false){
    try{
        if($views_only){
            sql_query('DELETE FROM `meta_history` WHERE `meta_id` = :meta_id AND `action` = "view"', array(':meta_id' => $meta_id), 'core');
            meta_action($meta_id, 'clear-views');

        }else{
            sql_query('DELETE FROM `meta_history` WHERE `meta_id` = :meta_id', array(':meta_id' => $meta_id), 'core');
            meta_action($meta_id, 'clear-history');
        }

        return $meta_id;

    }catch(Exception $e){
        throw new BException('meta_erase(): Failed', $e);
    }
}



/*
 * Add meta link for the specified row id in the specified table
 *
 * If a table record is missing its meta_id, then with this function one can be added directly
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package meta
 * @see meta_action()
 * @version 1.27.1: Added function and documentation
 * @example
 * code
 * // This will add a meta_id link for user with id 15
 * meta_link(15, 'users');
 * /code
 *
 * @param natural $table_id The id of the row that needs a meta_id link
 * @param string $table The table in which the meta_id link must be added
 * @return natural The meta id assigned to the specified $table_id entry
 */
function meta_link($table_id, $table){
    try{
        $exists = sql_get('SELECT `meta_id` FROM `'.$table.'` WHERE `id` = :id', true, array(':id' => $table_id), 'core');

        if($exists){
            /*
             * This entry already has a meta_id assigned
             */
            return false;
        }

        $meta_id = meta_action(null, 'linked');

        sql_query('UPDATE `'.$table.'`
                   SET     `meta_id` = :meta_id
                   WHERE   `id`      = :id',

                   array(':id'      => $table_id,
                         ':meta_id' => $meta_id), 'core');

        return $meta_id;

    }catch(Exception $e){
        throw new BException('meta_link(): Failed', $e);
    }
}
?>
