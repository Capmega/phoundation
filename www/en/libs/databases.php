<?php
/*
 * Databases library
 *
 * This library contains functoins to manage databases
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package database
 */



/*
 *
 */
function databases_validate_accounts($database) {
    global $_CONFIG;

    try{
        load_libs('validate,file,seo');

        $v = new ValidateForm($database, 'name,username,password,description');
// :TOO: Implement

        $v->isValid();
        return $database;

    }catch(Exception $e) {
        throw new CoreException('databases_validate_accounts(): Failed', $e);
    }
}



/*
 * Return data for the specified template
 *
 * This function returns information for the specified template. The template can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package database
 * @see sql_simple_get()
 * @see databases_list_accounts()
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The parameters for this get request
 * @param string $params[columns] The specific column that has to be returned
 * @param string $params[filters]
 * @param string $params[joins]
 * @return mixed The database account data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified template does not exist, NULL will be returned.
 */
function databases_get_account($params) {
    try{
        array_params($params, 'seoname', 'id');

        $params['table']     = 'database_accounts';
        $params['connector'] = 'core';

        array_default($params, 'filters', array('id'      => $params['id'],
                                                'seoname' => $params['seoname']));

        array_default($params, 'columns', 'id,
                                           createdon,
                                           createdby,
                                           meta_id,
                                           status,
                                           name,
                                           seoname,
                                           username,
                                           password,
                                           root_password,
                                           description');

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException(tr('databases_get_account(): Failed'), $e);
    }
}



/*
 * Return a list of all available databases_accounts filtered by
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @see sql_simple_list()
 * @see databases_get_account()
 * @package databases_accounts
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available databases_accounts
 */
function databases_list_accounts($params) {
    try{
        Arrays::ensure($params);

        $params['table']     = 'database_accounts';
        $params['connector'] = 'core';

        array_default($params, 'columns', array('seoname,name'));

        array_default($params, 'filters', array());
        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException(tr('databases_list_accounts(): Failed'), $e);
    }
}
?>
