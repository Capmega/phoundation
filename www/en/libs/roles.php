<?php
/*
 * Roles library
 *
 * This library contains funtions to work with the user roles
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Return requested data for specified role
 */
function roles_get($role = null) {
    global $_CONFIG;

    try {
        $query = 'SELECT    `roles`.`id`,
                            `roles`.`meta_id`,
                            `roles`.`name`,
                            `roles`.`status`,
                            `roles`.`createdon`,
                            `roles`.`description`,

                            `createdby`.`name`   AS `createdby_name`,
                            `createdby`.`email`  AS `createdby_email`

                  FROM      `roles`

                  LEFT JOIN `users` as `createdby`
                  ON        `roles`.`createdby`     = `createdby`.`id`';

        if ($role) {
            if (!is_string($role)) {
                throw new CoreException(tr('roles_get(): Specified role name ":name" is not a string', array(':name' => $role)), 'invalid');
            }

            $retval = sql_get($query.'

                              WHERE      `roles`.`name`   = :name
                              AND        `roles`.`status` IS NULL',

                              array(':name' => $role));

        } else {
            /*
             * Pre-create a new role
             */
            $retval = sql_get($query.'

                              WHERE  `roles`.`createdby` = :createdby

                              AND    `roles`.`status`    = "_new"',

                              array(':createdby' => $_SESSION['user']['id']));

            if (!$retval) {
                sql_query('INSERT INTO `roles` (`createdby`, `meta_id`, `status`, `name`)
                           VALUES              (:createdby , :meta_id , :status , :name )',

                           array(':name'      => $role,
                                 ':meta_id'   => meta_action(),
                                 ':status'    => '_new',
                                 ':createdby' => isset_get($_SESSION['user']['id'])));

                return roles_get($role);
            }
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('roles_get(): Failed', $e);
    }
}



/*
 *
 */
function roles_validate($role) {
    try {
        load_libs('validate');

        $v = new ValidateForm($role, 'name,description');

        $v->isNatural($role['id'], 1, tr('Invalid role id specified'));
        $v->isNotEmpty($role['name'], tr('No roles name specified'));
        $v->isRegex($role['name'], '/[a-z-]{2,32}/', tr('Please ensure the role\'s name is valid. It can only contain a-z and -, and no spaces'));

        $v->isNotEmpty ($role['description']      , tr('No role\'s description specified'));
        $v->hasMinChars($role['description'],    2, tr('Please ensure the role\'s description has at least 2 characters'));
        $v->hasMaxChars($role['description'], 2047, tr('Please ensure the role\'s description has less than 2047 characters'));

        if (is_numeric(substr($role['name'], 0, 1))) {
            $v->setError(tr('Please ensure that the role\'s name does not start with a number'));
        }

        /*
         * Also check if this is not the god right. If so, it CAN NOT be
         * updated
         */
        $name = sql_get('SELECT `name` FROM `roles` WHERE `id` = :id', 'name', array(':id' => $role['id']));

        if ($name === 'god') {
            $v->setError(tr('The role "god" cannot be modified'));
        }

        /*
         * This role does not exist yet?
         */
        $exists = sql_get('SELECT `name` FROM `roles` WHERE `name` = :name AND `id` != :id', 'name', array(':name' => $role['name'], ':id' => isset_get($role['id'], 0)));

        if ($exists) {
            $v->setError(tr('The role ":role" already exists', array(':role' => $role['name'])));
        }

        $v->isValid();

        return $role;

    }catch(Exception $e) {
        throw new CoreException(tr('roles_validate(): Failed'), $e);
    }
}



/*
 * Update the roles_rights and users_rights tables after an update of the
 * specified role
 */
function roles_update_rights($role, $rights) {
    try {
        if (empty($role['id'])) {
            throw new CoreException('roles_update_rights(): Cannot update rights, no role specified', 'not_specified');
        }

        if (isset_get($rights) and !is_array($rights)) {
            throw new CoreException('roles_update_rights(): The specified rights list is invalid', 'invalid');
        }

        /*
         * First check, clean rights and obtain both rights id and name
         */
        $rights_list = array();

        foreach ($rights as $key => $right) {
            if (!$right) {
                continue;
            }

            if (is_numeric($right)) {
                $rights_name = sql_get('SELECT `name` FROM `rights` WHERE `id` = :id', 'name', array(':id' => cfi($right)));

                if ($rights_name) {
                    $rights_list[$right] = $rights_name;
                    continue;
                }


            } else {
                $rights_id = sql_get('SELECT `id` FROM `rights` WHERE `name` = :name', 'id', array(':name' => cfm($right)));

                if ($rights_id) {
                    $rights_list[$rights_id] = $right;
                    continue;
                }
            }

            /*
             * Specified right does not exist.
             */
            throw new CoreException(tr('roles_update_rights(): Tried adding non existing right ":right" to role ":role", ignoring', array(':right' => $right, ':role' => $role['name'])), 'unknown');
        }

        /*
         * First update the roles_rights table.
         */
        sql_query('DELETE FROM `roles_rights` WHERE `roles_id` = :roles_id', array(':roles_id' => $role['id']));

        $p = sql_prepare('INSERT INTO `roles_rights` (`roles_id`, `rights_id`)
                          VALUES                     (:roles_id , :rights_id )');

        $role_right = array(':roles_id' => $role['id']);

        foreach ($rights_list as $rights_id => $name) {
            $p->execute(array(':roles_id'  => $role['id'],
                              ':rights_id' => $rights_id));
        }

        /*
         * Now update the users_rights table
         */
        $users  = sql_query('SELECT `id` FROM `users` WHERE `roles_id` = :roles_id', array(':roles_id' => $role['id']));

        $delete = sql_prepare('DELETE FROM `users_rights` WHERE `users_id` = :users_id');

        $insert = sql_prepare('INSERT INTO `users_rights` (`users_id`, `rights_id`, `name`)
                               VALUES                     (:users_id , :rights_id , :name )');

        while ($user = sql_fetch($users)) {
            $delete->execute(array(':users_id'  => $user['id']));

            foreach ($rights_list as $rights_id => $name) {
                $insert->execute(array(':users_id'  => $user['id'],
                                       ':rights_id' => $rights_id,
                                       ':name'      => $name));
            }
        }

        return $rights_list;

    }catch(Exception $e) {
        throw new CoreException('roles_update_rights(): Failed', $e);
    }
}
?>
