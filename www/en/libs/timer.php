<?php
/*
 * Timer library
 *
 * This library...
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



$core->register['timers'] = array();



/*
 * Register timer in the database
 */
function timer_start($process){
    try{
        sql_query('INSERT INTO `timers` (`createdby`, `process`, `start`)
                   VALUES               (:createdby , :process , NOW())',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':process'   => $process));

        $id = sql_insert_id();
        $core->register['timers'][$id] = microtime(true);

        return $id;

    }catch(Exception $e){
        throw new CoreException('timer_start(): Failed', $e);
    }
}



/*
 * Update existing timer in database with stop time
 */
function timer_stop($id){
    try{
        if(empty($core->register['timers'][$id])){
            throw new CoreException(tr('timer_stop(): Specified timers id %id%" is not registered as a timer', array('%id%' => $id)), 'not-exists');
        }

        $time = (integer) round((microtime(true) - $core->register['timers'][$id]) * 1000, 0);
        $r    = sql_query('UPDATE `timers`

                           SET `stop`   = NOW(),
                               `time`   = :time

                           WHERE `id` = :id',

                           array(':id'   => $id,
                                 ':time' => $time));

        if(!$r->rowCount()){
            throw new CoreException(tr('timer_stop(): Specified id %id%" exist in memory, but not in the database', array('%id%' => $id)), 'not-exists');
        }

        unset($core->register['timers'][$id]);

        return $time;

    }catch(Exception $e){
        throw new CoreException('timer_stop(): Failed', $e);
    }
}



/*
 * Return timer information for the specified process
 */
function timer_get($process, $type = 'average'){
    try{
        if($time = sql_get('SELECT AVG(`time`) AS `time` FROM `timers` WHERE `process` = :process', 'time', array(':process' => $process))){
            return $time;
        }

        throw new CoreException('timer_get(): Specified process "%process%" was not found', array('%process%' => $process), 'not-exists');

    }catch(Exception $e){
        throw new CoreException('timer_get(): Failed', $e);
    }
}
?>
