<?php
require_once(__DIR__.'/../libs/startup.php');

rights_or_access_denied('admin,roles');

$std_limit = 500;

$limit     = sql_valid_limit(isset_get($_GET['limit']), $std_limit);


/*
 * Process requested actions
 */
try{
    switch(isset_get($_POST['action'])){
        case '':
            break;

        case 'create':
            redirect(domain('/admin/role.php'));

        case 'delete':
            /*
             * Erase the specified roles
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot erase roles, no roles selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase roles, invalid data specified', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('UPDATE `roles` SET `status` = "deleted" WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user roles have been deleted', 'warning');

            }else{
                html_flash_set(log_database('Deleted "'.$r->rowCount().'" roles "', 'roles_deleted'), 'success');
            }

            break;

        case 'undelete':
            /*
             * Erase the specified roles
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot undelete roles, no roles selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot undelete roles, invalid data specified', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('UPDATE `roles` SET `status` = NULL WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user roles have been undeleted', 'warning');

            }else{
                html_flash_set(log_database('Undeleted "'.$r->rowCount().'" roles "', 'roles_undeleted'), 'success');
            }

            break;

        case 'erase':
            /*
             * Erase the specified roles
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot erase roles, no roles selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase roles, invalid data specified', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('DELETE FROM `roles` WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user roles have been erased', 'warning');

            }else{
                html_flash_set(log_database('Erased "'.$r->rowCount().'" roles "', 'roles_erased'), 'success');
            }

            break;

        default:
            /*
             * Unknown action specified
             */
            html_flash_set(tr('Unknown action "%action%" specified', '%action%', str_log($_POST['action'])), 'error');
    }

}catch(Exception $e){
    html_flash_set($e);
}


/*
 * Select sections dependant on the view
 */
switch(isset_get($_GET['view'])){
    case '':
        // FALLTHROUGH
    case 'normal':
        $where[] = '`roles`.`status` IS NULL';

        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create' => tr('Create new role'),
                                               'delete' => tr('Delete selected roles')));

        break;

    case 'deleted':
        $where[] = '`roles`.`status` = "deleted"';

        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('undelete' => tr('Undelete selected roles'),
                                               'erase'    => tr('Erase selected roles')));

        break;

    default:
        html_flash_set('Unknown view filter "'.str_log($_GET['view']).'" specified', 'error');
        redirect(false);
}


/*
 * Setup filters
 */
$views    = array('name'       => 'view',
                  'none'       => false,
                  'class'      => 'filter form-control mb-xs mt-xs mr-xs btn btn-default dropdown-toggle',
                  'autosubmit' => true,
                  'selected'   => isset_get($_GET['view']),
                  'resource'   => array('normal'  => tr('View normal roles'),
                                        'deleted' => tr('View deleted roles')));

$rights   = array('name'       => 'right',
                  'class'      => 'filter form-control mb-xs mt-xs mr-xs btn btn-default dropdown-toggle',
                  'none'       => tr('Show all rights'),
                  'autosubmit' => true,
                  'selected'   => isset_get($_GET['right']),
                  'resource'   => sql_query('SELECT `name` AS `id`, `name` FROM `rights` ORDER BY `name`'));


/*
 * Build and execute query
 */
$execute = array();

$query   = 'SELECT    `roles`.`id`,
                      `roles`.`name`,
                      `roles`.`description`,
                      `roles`.`createdon`,
                      `users`.`name` AS `createdby`

            FROM      `roles`

            LEFT JOIN `users`
            ON        `users`.`id`   = `roles`.`createdby`';


/*
 * Apply role filter
 */
if(!empty($_GET['role'])){
    $where[] = ' `roles`.`name` = :role';
    $execute = array(':role' => $_GET['role']);
}


/*
 * Apply generic filter
 */
if(!empty($_GET['filter'])){
    $where[]              = ' (`roles`.`name` LIKE :name OR `users`.`name` LIKE :username)';
    $execute[':name']     = '%'.$_GET['filter'].'%';
    $execute[':username'] = '%'.$_GET['filter'].'%';
}


/*
 * Execute query
 */
if(!empty($where)){
    $query .= ' WHERE '.implode(' AND ', $where);
}

$query .= ' ORDER BY `roles`.`name`';

if($limit){
    $query .= ' LIMIT '.$limit;
}

$r = sql_query($query, $execute);


/*
 * Build HTML
 */
$html    = '<div class="row">
                <div class="col-md-12">
                    <section class="panel">
                        <header class="panel-heading">
                            <h2 class="panel-title">'.tr('User roles').'</h2>
                            <p>
                                '.html_flash().'
                                <div class="form-group">
                                    <div class="col-sm-12">
                                        <form action="'.domain(true).'" method="get">
                                            <div class="row">
                                                <div class="col-sm-2">
                                                    '.html_select($views).'
                                                </div>
                                                <div class="visible-xs mb-md"></div>
                                                <div class="col-sm-2">
                                                    '.html_select($rights).'
                                                </div>
                                                <div class="col-sm-2">
                                                    <div class="input-group input-group-icon">
                                                        <input type="text" class="form-control col-md-3" name="filter" id="filter" value="'.str_log(isset_get($_GET['filter'], '')).'" placeholder="General filter">
                                                        <span class="input-group-addon">
                                                            <span class="icon"><i class="fa fa-search"></i></span>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-sm-2">
                                                    <input type="text" class="form-control col-md-3" name="limit" id="limit" value="'.str_log(isset_get($_GET['limit'], '')).'" placeholder="'.tr('Row limit (default %entries% entries)', array('%entries%' => str_log($std_limit))).'">
                                                </div>
                                                <div class="visible-xs mb-md"></div>
                                                <div class="col-sm-2">
                                                    <input type="submit" class="mb-xs mr-xs btn btn-sm btn-primary" name="reload" id="reload" value="'.tr('Reload').'">
                                                </div>
                                                <div class="visible-xs mb-md"></div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </p>
                        </header>
                        <form action="'.domain(true).'" method="post">
                            <div class="panel-body">';

if(!$r->rowCount()){
    $html .= '<p>'.tr('No roles were found with the current filter').'</p>';

}else{
    $html .= '  <div class="table-responsive">
                    <table class="select link table mb-none table-striped table-hover">
                        <thead>
                            <th class="select"><input type="checkbox" name="id[]" class="all"></th>
                            <th>'.tr('Name').'</th>
                            <th>'.tr('Created by').'</th>
                            <th>'.tr('Created on').'</th>
                            <th>'.tr('Description').'</th>
                            <th>'.tr('Rights').'</th>
                        </thead>';

    while($role = sql_fetch($r)){
        $a                 = '<a href="'.domain('/admin/role.php?role='.$role['name']).'">';

        $role['createdon'] = new DateTime($role['createdon']);
        $role['createdon'] = $role['createdon']->format($_CONFIG['formats']['human_datetime']);

        $role['rights']    = sql_list('SELECT `name`

                                       FROM   `rights`

                                       JOIN   `roles_rights`
                                       ON     `roles_rights`.`rights_id` = `rights`.`id`
                                       AND    `roles_rights`.`roles_id`  = :roles_id',

                                       array(':roles_id' => $role['id']));

        if(!empty($_GET['right'])){
            /*
             * Filter by the specified right.
             */
            if(!in_array($_GET['right'], $role['rights'])){
                continue;
            }
        }

        $html .= '  <tr>
                        <td class="select"><input type="checkbox" name="id[]" value="'.$role['id'].'"></td>
                        <td>'.$a.$role['name'].'</a></td>
                        <td>'.$a.$role['createdby'].'</a></td>
                        <td>'.$a.$role['createdon'].'</a></td>
                        <td>'.$a.$role['description'].'</a></td>
                        <td>'.$a.str_force($role['rights'], ', ').'</a></td>
                    </tr>';
    }

    $html .= '  </table>
            </div>';
}

$html .=                    html_select($actions).'
                        </div>
                    </form>
                </section>
            </div>
        </div>';

log_database('Viewed user roles', 'roles_viewed');

$params = array('icon'        => 'fa-lock',
                'title'       => tr('roles'),
                'breadcrumbs' => array(tr('roles'), tr('Manage')));

echo ca_page($html, $params);
?>
