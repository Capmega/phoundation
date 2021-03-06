<?php
/*
 * user_switch() handler
 *
 * This snippet will switch the current session user to the specified new user
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */
try{
    /*
     * Does the specified user exist?
     */
    if(!$user = sql_get('SELECT *, `email` FROM `users` WHERE `id` = :id', array(':id' => $users_id))){
        throw new BException(tr('user_switch(): The specified user ":id" does not exist', array(':id' => $users_id)), 'not-exists');
    }

    /*
     * Only god users may perform user switching
     */
    if(has_rights('god')){
        /*
         * Switch the current session to the new user
         * Store last login
         * Register this action
         */
        $from = $_SESSION['user'];

        /*
         * Optionally, load extra data
         */
        if($user['employees_id']){
            /*
             * Load employee data
             */
            load_libs('companies');
            $user['employee'] = companies_get_employee($user['employees_id']);

            if($user['employee']['customers_id']){
                /*
                 * Load customers data
                 */
                load_libs('customers');
                $user['customer'] = customers_get(array('id'      => $user['employee']['customers_id'],
                                                        'columns' => 'name,seoname'));
            }

            if($user['employee']['providers_id']){
                /*
                 * Load providers data
                 */
                load_libs('providers');
                $user['provider'] = providers_get(array('id'      => $user['employee']['providers_id'],
                                                        'columns' => 'name,seoname'));
            }
        }

        $_SESSION['user'] = $user;

        sql_query('UPDATE `users`

                   SET    `last_signin` = DATE(NOW())

                   WHERE  `id` = :id',

                   array(':id' => cfi($user['id'])));

    }else{
        $status = 'denied';
        $from   = $user;
    }

    sql_query('INSERT INTO `users_switch` (`createdby`, `users_id`, `status`)
               VALUES                     (:createdby , :users_id , :status )',

               array(':users_id'  => cfi($user['id']),
                     ':createdby' => cfi($from['id']),
                     ':status'    => isset_get($status)));



    /*
     * If all is okay, then swith user!
     */
    if(empty($status)){
        log_database(tr('Executing user switch from ":from" to ":to"', array(':from' => name($from), ':to' => name($_SESSION['user']))), 'user/switch');

        html_flash_set(tr('You are now the user ":user"', array(':user' => name($user))), 'success');
        html_flash_set(tr('You will now be limited to the access level of user ":user"', array(':user' => name($user))), 'warning');

        if($redirect){
            redirect($redirect);
        }
    }



    /*
     * Not all ok? then fail
     */
    log_database(tr('Denied user switch from ":from" to ":to"', array(':from' => name($from), ':to' => name($_SESSION['user']))), 'user/switch');
    throw new BException(tr('user_switch(): The user ":user" does not have the required rights to perform user switching', array(':user' => name($_SESSION['user']))), 'access-denied');

}catch(Exception $e){
    throw new BException('user_switch(): Failed', $e);
}
?>
