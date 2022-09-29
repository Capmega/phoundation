<?php
/*
 * Rights library
 *
 * This is the rights library file, it contains rights functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Give the specified rights to the specified users
 */
function rights_give($users, $rights) {
    try{
        $users  = Arrays::force($users);
        $rights = Arrays::force($rights);

        /*
         * Ensure we have all users id's
         */
        foreach($users as $key => $value) {
            /*
             * Ensure that the specified user exists (either id or name)
             */
            if (!is_numeric($value)) {
                $users[$key] = sql_get('SELECT `id`

                                        FROM   `users`

                                        WHERE  `username` = :username
                                        OR     `email`    = :email',

                                        array(':username' => $value,
                                              ':email'    => $value), 'id');

                if (!$users[$key]) {
                    /*
                     * This user does not exist...
                     */
                    throw new CoreException(tr('rights_give(): The specified user ":user" does not exist', array(':user' => $value)), 'not-exists');
                }

            } else {
                if (!sql_get('SELECT `id` FROM `users` WHERE `id` = :id', array(':id' => $value), 'id')) {
                    /*
                     * This user does not exist...
                     */
                    throw new CoreException(tr('rights_give(): The specified users id ":users_id" does not exist', array(':users_id' => $value)), 'not-exists');
                }
            }
        }

        /*
         * Ensure we have all rights id's
         */
        foreach($rights as $key => $value) {
            if (!is_numeric($value)) {
                if (!$rights[$key] = sql_get('SELECT `id`, `name` FROM `rights` WHERE `name` = :name', array(':name' => $value))) {
                    /*
                     * This right does not exist...
                     */
                    throw new CoreException(tr('rights_give(): The specified right ":right" does not exist', array(':right' => $value)), 'not-exists');
                }

            } else {
                if (!$rights[$key] = sql_get('SELECT `id`, `name` FROM `rights` WHERE `id` = :id', array(':id' => $value))) {
                    /*
                     * This right does not exist...
                     */
                    throw new CoreException(tr('rights_give(): The specified rights id ":rights_id" does not exist', array(':rights_id' => $value)), 'not-exists');
                }
            }
        }

        $p = sql_prepare('INSERT INTO `users_rights` (`addedby`, `users_id`, `rights_id`, `name`)
                          VALUES                     (:addedby , :users_id , :rights_id , :name )');

        $r = sql_prepare('SELECT `id` FROM `users_rights` WHERE `users_id` = :users_id AND `rights_id` = :rights_id');

        foreach($users as $user) {
            foreach($rights as $right) {
                try{
                    /*
                     * Only add the right if the user does not yet have it
                     */
                    $execute = array(':users_id' => $user, ':rights_id' => $right['id']);

                    $r->execute($execute);

                    if (!sql_fetch($r, 'id')) {
                        try{
                            $execute = array(':addedby'   => $user,
                                             ':users_id'  => $user,
                                             ':rights_id' => $right['id'],
                                             ':name'      => $right['name']);

                            $p->execute($execute);

                        }catch(Exception $e) {
                            sql_error($e, $p->queryString, isset_get($execute));
                        }
                    }

                }catch(Exception $e) {
                    sql_error($e, $r->queryString, isset_get($execute));
                }
            }
        }

    }catch(Exception $e) {
        throw new CoreException('rights_give(): Failed', $e);
    }
}



/*
 * Take the specified rights from the specified users
 */
function rights_take($users, $rights) {
    try{
        $users  = Arrays::force($users);
        $rights = Arrays::force($rights);

        /*
         * Ensure we have all users id's
         */
        foreach($users as $key => $value) {
            /*
             * Ensure that the specified user exists (either id or name)
             */
            if (!is_numeric($value)) {
                if (!$users[$key] = sql_get('SELECT `id` FROM `users` WHERE `username` = :username OR `email` = :email', array(':username' => $value, ':email' => $value), 'id')) {
                    /*
                     * This user does not exist...
                     */
                    throw new CoreException(tr('rights_give(): The specified user ":user" does not exist', array(':users_id' => $value)), 'not-exists');
                }

            } else {
                if (!sql_get('SELECT `id` FROM `users` WHERE `id` = :id', array(':id' => $value), 'id')) {
                    /*
                     * This user does not exist...
                     */
                    throw new CoreException(tr('rights_give(): The specified users id ":users_id" does not exist', array(':users_id' => $value)), 'not-exists');
                }
            }
        }

        /*
         * Ensure we have all rights id's
         */
        foreach($rights as $key => $value) {
            if (!is_numeric($value)) {
                if (!$rights[$key] = sql_get('SELECT `id` FROM `rights` WHERE `name` = :name', array(':name' => $value), 'id')) {
                    /*
                     * This right does not exist...
                     */
                    throw new CoreException(tr('rights_give(): The specified right ":right" does not exist', array(':right' => $value)), 'not-exists');
                }

            } else {
                if (!sql_get('SELECT `id` FROM `rights` WHERE `id` = :id', array(':id' => $value), 'id')) {
                    /*
                     * This right does not exist...
                     */
                    throw new CoreException(tr('rights_give(): The specified rights id ":rights_id" does not exist', array(':rights_id' => $value)), 'not-exists');
                }
            }
        }

        $p = sql_prepare('DELETE FROM `users_rights` WHERE `users_id` = :users_id AND `rights_id` = :rights_id');

        foreach($users as $user) {
            foreach($rights as $right) {
                try{
                    $execute = array(':users_id'  => $user,
                                     ':rights_id' => $right);

                    $p->execute($execute);

                }catch(Exception $e) {
                    sql_error($e, $p->queryString, isset_get($execute));
                }
            }
        }

    }catch(Exception $e) {
        throw new CoreException('rights_take(): Failed', $e);
    }
}



/*
 * Return requested data for specified rights
 */
function rights_get($right) {
    try{
        if (!$right) {
            throw new CoreException(tr('rights_get(): No right specified'), 'not-specified');
        }

        if (!is_scalar($right)) {
            throw new CoreException(tr('rights_get(): Specified right ":right" is not scalar', array(':right' => $right)), 'invalid');
        }

        $retval = sql_get('SELECT    `rights`.`id`,
                                     `rights`.`meta_id`,
                                     `rights`.`name`,
                                     `rights`.`status`,
                                     `rights`.`description`,

                                     `createdby`.`name`   AS `createdby_name`,
                                     `createdby`.`email`  AS `createdby_email`

                           FROM      `rights`

                           LEFT JOIN `users` AS `createdby`
                           ON        `rights`.`createdby`  = `createdby`.`id`

                           WHERE     `rights`.`id`   = :right
                           OR        `rights`.`name` = :right',

                           array(':right' => $right));

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('rights_get(): Failed', $e);
    }
}



/*
 * Return an HTML select containing all posisble rights
 */
// :TODO: Reimplement this using html_select()
function rights_select($select = '', $name = 'rights_id', $god = true) {
    global $pdo;

    try{
        if ($retval = cache_read('rights_'.$name.'_'.$select.($god ? '_all' : ''))) {
            return $retval;
        }

        $retval = '<select class="categories" name="'.$name.'">';

        if ($god) {
            $retval .= '<option value="0"'.(!$select ? ' selected' : '').'>All categories</option>';
        }

        foreach(rights_list() as $right) {
            $retval .= '<option value="'.$right['id'].'"'.(($right['id'] == $select) ? ' selected' : '').'>'.str_replace('_', ' ', str_camelcase($right['name'])).'</option>';
        }

        return cache_write('rights_'.$name.'_'.$select.($god ? '_all' : ''), $retval.'</select>');

    }catch(Exception $e) {
        throw new CoreException('rights_select(): Failed', $e);
    }
}



/*
 * Return if the specified user has the specified right.
 *
 * NOTE: This function does NOT keep track of "god" and "devil" rights!
 * NOTE: This user ONLY checks rights, so "admin" right column in user table is also ignored!
 */
function rights_has($user, $right) {
    try{
        if (is_array($user)) {
            $user = array_extract_first($user, 'id,email,name');
        }

        if (!$target = sql_get('SELECT `id` FROM `users` WHERE `id` = :id OR `name` = :name OR `email` = :email', array(':name' => $user, ':email' => $user, ':id' => $user), 'id')) {
            throw new CoreException(tr('rights_has(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exists');
        }

        $rights = sql_list('SELECT `users_id`,
                                   `rights_id`,
                                   `addedby`,
                                   `addedon`,
                                   `name`

                            FROM   `users_rights`

                            WHERE  `users_id` = :users_id',

                            array(':users_id' => $target));

        if (empty($rights[$right])) {
            /*
             * Requested right not found
             */
            return false;
        }

        return true;

    }catch(Exception $e) {
        throw new CoreException('rights_has(): Failed', $e);
    }
}



/*
 *
 */
function rights_validate($right, $old_right = null) {
    try{
        load_libs('validate');

        if ($old_right) {
            $right = array_merge($old_right, $right);
        }

        $v = new ValidateForm($right, 'id,name,description');
        $v->isNotEmpty ($right['name']    , tr('No rights name specified'));
        $v->hasMinChars($right['name'],  2, tr('Please ensure the right\'s name has at least 2 characters'));
        $v->hasMaxChars($right['name'], 32, tr('Please ensure the right\'s name has less than 32 characters'));
        $v->isRegex    ($right['name'], '/^[a-z-]{2,32}$/', tr('Please ensure the right\'s name contains only lower case letters, and dashes'));

        if ($right['description']) {
            $v->hasMinChars($right['description'],    2, tr('Please ensure the right\'s description has at least 2 characters'));
            $v->hasMaxChars($right['description'], 2047, tr('Please ensure the right\'s description has less than 2047 characters'));
            $v->hasNoHTML($right['description']);

        } else {
            $right['description'] = '';
        }

        if (is_numeric(substr($right['name'], 0, 1))) {
            $v->setError(tr('Please ensure that the rights\'s name does not start with a number'));
        }

        /*
         * Does the right already exist?
         */
        if ($id = sql_get('SELECT `id` FROM `rights` WHERE `name` = :name AND `id` != :id', array(':name' => $right['name'], ':id' => $right['id']))) {
            $v->setError(tr('The right ":right" already exists with id ":id"', array(':right' => $right['name'], ':id' => $id)));
        }

        if (!empty($right['id'])) {
            /*
             * Check if this is not the god right. If so, it CAN NOT be
             * updated
             */
            $name = sql_get('SELECT `name` FROM `rights` WHERE `id` = :id', 'name', array(':id' => $right['id']));

            if ($name === 'god') {
                $v->setError(tr('The right "god" cannot be modified'));
            }
        }

        $v->isValid();

        return $right;

    }catch(Exception $e) {
        throw new CoreException(tr('rights_validate(): Failed'), $e);
    }
}
?>
